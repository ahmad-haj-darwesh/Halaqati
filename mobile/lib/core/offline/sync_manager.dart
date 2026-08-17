import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import '../../services/api/api_client.dart';
import '../../storage/token_storage.dart';
import '../errors/api_exception.dart';
import 'connectivity_service.dart';
import 'offline_database.dart';
import 'offline_models.dart';
import 'outbox_dao.dart';

/// مدير تفريغ طابور المزامنة.
///
/// Arabic: يمرّ على العمليات المؤجلة بالترتيب ويرسلها. الفكرة المحورية هي التمييز بين
/// نوعي الفشل: فشل **مؤقت** (شبكة/مهلة/خطأ خادم) يستحق إعادة محاولة بتباعد متزايد،
/// وفشل **نهائي** (تحقق/صلاحية) لا تنفع معه إعادة المحاولة أبداً وإعادتها إلى ما لا
/// نهاية تعني طابوراً عالقاً وبطارية مستنزفة — فيُوسم للمستخدم ليقرر.
/// EN: Drains the outbox, distinguishing retryable failures (network/5xx) from
/// permanent ones (validation/authorization) that must surface to the user.
class SyncManager {
  final OutboxDao _outbox;
  final ApiClient _apiClient;
  final ConnectivityService _connectivity;
  final TokenStorage _tokenStorage;

  final ValueNotifier<SyncSnapshot> snapshot = ValueNotifier(const SyncSnapshot());

  StreamSubscription<bool>? _connectivitySubscription;
  Timer? _periodicTimer;
  bool _isDraining = false;

  /// أقصى عدد محاولات لفشل مؤقت قبل وسمه ليراجعه المستخدم.
  static const int maxAttempts = 8;

  /// دورة مزامنة احتياطية تلتقط ما فاتته أحداث الشبكة.
  static const Duration periodicInterval = Duration(minutes: 5);

  SyncManager({
    required OutboxDao outbox,
    required ApiClient apiClient,
    required ConnectivityService connectivity,
    required TokenStorage tokenStorage,
  })  : _outbox = outbox,
        _apiClient = apiClient,
        _connectivity = connectivity,
        _tokenStorage = tokenStorage;

  /// بدء المراقبة: عند عودة الشبكة، وبشكل دوري احتياطي.
  Future<void> start() async {
    if (!OfflineDatabase.isSupported) return;

    snapshot.value = snapshot.value.copyWith(isOnline: await _connectivity.isOnline);

    _connectivitySubscription ??= _connectivity.onStatusChange.listen((online) {
      snapshot.value = snapshot.value.copyWith(isOnline: online);
      if (online) {
        unawaited(sync());
      }
    });

    _periodicTimer ??= Timer.periodic(periodicInterval, (_) => unawaited(sync()));

    await refreshCounts();
    unawaited(sync());
  }

  /// تحديث عدّادات الواجهة دون محاولة إرسال.
  Future<void> refreshCounts() async {
    if (!OfflineDatabase.isSupported) return;

    final userKey = await _tokenStorage.getUserKey();
    if (userKey == null) {
      snapshot.value = snapshot.value.copyWith(pendingCount: 0, failedCount: 0);
      return;
    }

    final pending = await _outbox.countByStatus(userKey, OutboxStatus.pending);
    final failed = await _outbox.countByStatus(userKey, OutboxStatus.failed);

    snapshot.value = snapshot.value.copyWith(pendingCount: pending, failedCount: failed);
  }

  /// تفريغ الطابور مرة واحدة.
  ///
  /// Arabic: يتوقف عند أول فشل مؤقت بدل الاستمرار — لأن انقطاع الشبكة سيُفشل كل ما
  /// بعده على أي حال، والاستمرار يعني إحراق عدّاد المحاولات لعمليات سليمة.
  /// EN: One drain pass; stops at the first retryable failure so a network outage
  /// does not burn the retry budget of every queued item.
  Future<void> sync() async {
    if (!OfflineDatabase.isSupported || _isDraining) return;

    final userKey = await _tokenStorage.getUserKey();
    if (userKey == null) return;

    _isDraining = true;
    snapshot.value = snapshot.value.copyWith(isSyncing: true);

    try {
      final due = await _outbox.dueEntries(userKey, DateTime.now());
      var sentAny = false;

      for (final entry in due) {
        final outcome = await _send(entry);

        if (outcome == _SendOutcome.transientFailure) break;
        if (outcome == _SendOutcome.success) sentAny = true;
      }

      if (sentAny) {
        snapshot.value = snapshot.value.copyWith(lastSyncedAt: DateTime.now());
      }
    } finally {
      _isDraining = false;
      snapshot.value = snapshot.value.copyWith(isSyncing: false);
      await refreshCounts();
    }
  }

  /// إرسال مباشر يتجاوز الطابور — تستخدمه البوابة لمحاولة «الآن» قبل التأجيل.
  ///
  /// Arabic: يمرّ عبر نفس عميل الـ API فترتد أخطاؤه كـ `ApiException`، وهو ما تعتمد
  /// عليه البوابة للتمييز بين انقطاع الشبكة ورفض الخادم.
  /// EN: Direct send used by the gateway's try-now path.
  Future<Response> sendNow(String endpoint, Map<String, dynamic> payload) {
    return _apiClient.post(endpoint, data: payload);
  }

  Future<_SendOutcome> _send(OutboxEntry entry) async {
    final id = entry.id;
    if (id == null) return _SendOutcome.permanentFailure;

    try {
      await _apiClient.post(entry.endpoint, data: entry.payload);
      await _outbox.delete(id);
      return _SendOutcome.success;
    } on ApiException catch (e) {
      final attempts = entry.attempts + 1;

      if (_isPermanent(e.statusCode)) {
        await _outbox.markFailed(id, attempts, e.message);
        return _SendOutcome.permanentFailure;
      }

      if (attempts >= maxAttempts) {
        await _outbox.markFailed(id, attempts, e.message);
        return _SendOutcome.permanentFailure;
      }

      await _outbox.markRetry(id, attempts, DateTime.now().add(backoffFor(attempts)), e.message);
      return _SendOutcome.transientFailure;
    } catch (e) {
      final attempts = entry.attempts + 1;
      await _outbox.markRetry(id, attempts, DateTime.now().add(backoffFor(attempts)), e.toString());
      return _SendOutcome.transientFailure;
    }
  }

  /// هل الخطأ نهائي لا تنفع معه إعادة المحاولة؟
  ///
  /// Arabic: أخطاء 4xx تعني أن الطلب نفسه مرفوض (تحقق/صلاحية/تاريخ خارج النافذة)،
  /// عدا 408 و429 فهما مؤقتان بطبيعتهما. أما 401 فنعامله كمؤقت لأن التوكن قد يُجدَّد
  /// بإعادة تسجيل الدخول، وإسقاط بيانات المعلّم لمجرد انتهاء الجلسة غير مقبول.
  /// EN: 4xx is permanent except 408/429 (inherently transient) and 401 (recoverable
  /// by re-login — dropping the teacher's work over an expired session is not ok).
  @visibleForTesting
  static bool isPermanentStatus(int? statusCode) {
    if (statusCode == null) return false;
    if (statusCode == 408 || statusCode == 429 || statusCode == 401) return false;
    return statusCode >= 400 && statusCode < 500;
  }

  bool _isPermanent(int? statusCode) => isPermanentStatus(statusCode);

  /// تباعد أُسّي مع سقف، لتفادي استنزاف البطارية عند انقطاع طويل.
  /// EN: Capped exponential backoff.
  @visibleForTesting
  static Duration backoffFor(int attempts) {
    const capSeconds = 30 * 60;
    final seconds = (1 << attempts.clamp(1, 12)) * 5;
    return Duration(seconds: seconds > capSeconds ? capSeconds : seconds);
  }

  /// إعادة محاولة عنصر فاشل بأمر المستخدم.
  Future<void> retryEntry(int id) async {
    await _outbox.retry(id);
    await refreshCounts();
    await sync();
  }

  /// حذف عنصر من الطابور بأمر المستخدم (تخلٍّ صريح عن البيانات).
  Future<void> discardEntry(int id) async {
    await _outbox.delete(id);
    await refreshCounts();
  }

  /// كل عناصر الطابور للمستخدم الحالي.
  Future<List<OutboxEntry>> entries() async {
    if (!OfflineDatabase.isSupported) return const [];

    final userKey = await _tokenStorage.getUserKey();
    if (userKey == null) return const [];

    return _outbox.all(userKey);
  }

  /// إيقاف المراقبة.
  Future<void> dispose() async {
    await _connectivitySubscription?.cancel();
    _connectivitySubscription = null;
    _periodicTimer?.cancel();
    _periodicTimer = null;
    snapshot.dispose();
  }
}

enum _SendOutcome { success, transientFailure, permanentFailure }
