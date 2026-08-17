import '../../storage/token_storage.dart';
import '../errors/api_exception.dart';
import 'offline_database.dart';
import 'offline_models.dart';
import 'outbox_dao.dart';
import 'sync_manager.dart';

/// إشارة إلى أن العملية حُفظت محلياً بانتظار الشبكة.
///
/// Arabic: ليست فشلاً — البيانات محفوظة ولن تضيع. نوع مستقل حتى تعرض الواجهة رسالة
/// «حُفظ على الجهاز وسيُرسل تلقائياً» بدل رسالة خطأ تدفع المعلّم لإعادة الإدخال.
/// EN: Signals a write was queued locally; the UI reassures instead of erroring.
class QueuedOfflineException implements Exception {
  final String message;

  const QueuedOfflineException([
    this.message = 'لا يوجد اتصال. حُفظت البيانات على الجهاز وستُرسل تلقائياً عند عودة الإنترنت.',
  ]);

  @override
  String toString() => message;
}

/// بوابة الكتابة المؤجلة التي تستعملها مغلّفات المستودعات.
///
/// Arabic: مسؤولة عن قرار واحد: هل نرسل الآن أم نُدرج في الطابور؟ (كاش القراءة يعالَج
/// في `CachingApiClient` على مستوى HTTP.)
/// EN: Write-path gateway; the read path lives in `CachingApiClient`.
class OfflineGateway {
  final OutboxDao _outbox;
  final TokenStorage _tokenStorage;
  final SyncManager _syncManager;

  const OfflineGateway({
    required OutboxDao outbox,
    required TokenStorage tokenStorage,
    required SyncManager syncManager,
  })  : _outbox = outbox,
        _tokenStorage = tokenStorage,
        _syncManager = syncManager;

  /// كتابة: محاولة فورية، وإدراج في الطابور عند تعذّر الاتصال وحده.
  ///
  /// Arabic: نحاول الإرسال أولاً لأن الحالة الشائعة وجود شبكة، فلا معنى لتأخير تأكيد
  /// النجاح. و`client_recorded_at` تُرسل في الحالتين ليحسب الخادم نافذة تعديل التقييم
  /// من لحظة إدخال المستخدم لا من لحظة وصول الطلب.
  /// EN: Try-now, queue-on-connectivity-failure; the client timestamp always ships.
  ///
  /// تُعيد جسم استجابة الخادم عند الإرسال الفوري، وترمي [QueuedOfflineException] عند
  /// الإدراج في الطابور. أما [ApiException] فتُمرَّر كما هي عند رفض الخادم، لأن ذلك
  /// خطأ حقيقي في البيانات لا في الشبكة ولا تنفع معه إعادة المحاولة.
  ///
  /// Arabic: إعادة الجسم هنا ضرورية حتى لا يضطر المستدعي لإرسال الطلب مرة ثانية
  /// ليحصل على النتيجة — وهو ما كان سينشئ سجلاً مكرراً على الخادم.
  /// EN: Returns the response body so callers never re-issue the request to read it.
  Future<Map<String, dynamic>> write({
    required String endpoint,
    required Map<String, dynamic> payload,
    required String label,
    String? dedupeKey,
    DateTime? clientRecordedAt,
  }) async {
    final recordedAt = clientRecordedAt ?? DateTime.now();
    final body = {
      ...payload,
      'client_recorded_at': formatTimestamp(recordedAt),
    };

    final userKey = await _tokenStorage.getUserKey();
    final canQueue = OfflineDatabase.isSupported && userKey != null;

    try {
      final response = await _syncManager.sendNow(endpoint, body);
      final data = response.data;

      return data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{};
    } on ApiException catch (e) {
      if (!canQueue || e.statusCode != null) rethrow;

      await _outbox.enqueue(OutboxEntry(
        userKey: userKey,
        endpoint: endpoint,
        payload: body,
        dedupeKey: dedupeKey,
        label: label,
        clientRecordedAt: recordedAt,
        updatedAt: DateTime.now(),
      ));

      await _syncManager.refreshCounts();
      throw const QueuedOfflineException();
    }
  }

  /// هل توجد عملية منتظرة بهذا المفتاح؟ (لعرض «بانتظار الإرسال» في الواجهة)
  Future<bool> hasPending(String dedupeKey) async {
    final userKey = await _tokenStorage.getUserKey();
    if (!OfflineDatabase.isSupported || userKey == null) return false;

    final entries = await _outbox.all(userKey);
    return entries.any((e) => e.dedupeKey == dedupeKey && e.status == OutboxStatus.pending);
  }

  /// صياغة الوقت بصيغة `Y-m-d H:i:s` التي يفهمها Laravel، بالتوقيت المحلي للجهاز.
  /// EN: Formats a local timestamp the way Laravel's date validation expects.
  static String formatTimestamp(DateTime value) {
    final v = value.toLocal();
    String two(int n) => n.toString().padLeft(2, '0');
    return '${v.year}-${two(v.month)}-${two(v.day)} '
        '${two(v.hour)}:${two(v.minute)}:${two(v.second)}';
  }
}
