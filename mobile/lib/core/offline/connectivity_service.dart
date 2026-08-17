import 'dart:async';

import 'package:connectivity_plus/connectivity_plus.dart';

/// مراقبة حالة الشبكة.
///
/// Arabic: `connectivity_plus` يخبرنا عن **واجهة الشبكة** لا عن الوصول الفعلي للخادم —
/// فقد يتصل الجهاز بواي‑فاي بلا إنترنت. لذلك نعامله كمؤشّر لبدء المزامنة فقط،
/// بينما الحكم النهائي على النجاح يبقى لنتيجة الطلب نفسه.
/// EN: Network status stream. Treated as a hint to trigger sync, not as proof of
/// reachability — the request outcome remains the source of truth.
abstract class ConnectivityService {
  /// هل توجد واجهة شبكة فعّالة الآن؟
  Future<bool> get isOnline;

  /// بثّ يصدر عند كل تغيّر في حالة الشبكة.
  Stream<bool> get onStatusChange;

  /// تحرير الموارد.
  Future<void> dispose();
}

/// التنفيذ المعتمد على حزمة `connectivity_plus`.
class ConnectivityPlusService implements ConnectivityService {
  final Connectivity _connectivity;
  final StreamController<bool> _controller = StreamController<bool>.broadcast();
  StreamSubscription<List<ConnectivityResult>>? _subscription;
  bool? _lastStatus;

  ConnectivityPlusService({Connectivity? connectivity})
      : _connectivity = connectivity ?? Connectivity() {
    _subscription = _connectivity.onConnectivityChanged.listen((results) {
      final online = _isOnline(results);
      // لا نُغرق المستمعين بأحداث متطابقة عند تنقّل الجهاز بين شبكات متصلة.
      if (online == _lastStatus) return;
      _lastStatus = online;
      _controller.add(online);
    });
  }

  @override
  Future<bool> get isOnline async {
    final results = await _connectivity.checkConnectivity();
    return _isOnline(results);
  }

  @override
  Stream<bool> get onStatusChange => _controller.stream;

  bool _isOnline(List<ConnectivityResult> results) {
    return results.isNotEmpty && !results.every((r) => r == ConnectivityResult.none);
  }

  @override
  Future<void> dispose() async {
    await _subscription?.cancel();
    await _controller.close();
  }
}
