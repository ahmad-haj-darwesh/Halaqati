import 'dart:convert';

/// كاش استجابات القراءة (GET) في الذاكرة.
///
/// Arabic: يمنع إعادة تحميل الصفحة في كل مرة يعود إليها المستخدم. المخزن ثابت
/// (static) عمداً حتى تتشارك كل نسخ `ApiClient` نفس الكاش — فبعض الصفحات تنشئ
/// عميلاً خاصاً بها بدل الحصول عليه من حاوية الحقن.
/// العمر الافتراضي قصير، والكاش كله يزول بإغلاق التطبيق لأنه في الذاكرة فقط.
///
/// EN: In-memory GET response cache shared by every ApiClient instance.
class ResponseCache {
  ResponseCache._();

  /// العمر الافتراضي لأي مدخل قبل اعتباره منتهياً.
  static Duration defaultTtl = const Duration(minutes: 5);

  /// أعمار مخصّصة لمسارات بعينها (تُطابق ببداية المسار).
  static final Map<String, Duration> ttlOverrides = {
    // بيانات شبه ثابتة — عمر أطول
    '/teacher/me': const Duration(minutes: 30),
    '/supervisor/me': const Duration(minutes: 30),
    '/supervisor/centers': const Duration(minutes: 30),
    '/supervisor/teachers': const Duration(minutes: 30),
    // بيانات يومية تتغيّر كثيراً — عمر أقصر
    '/teacher/daily': const Duration(minutes: 2),
  };

  static final Map<String, _CacheEntry> _store = {};

  /// مفتاح فريد للطلب: المسار + معاملات الاستعلام مرتّبة.
  static String keyFor(String path, Map<String, dynamic>? query) {
    if (query == null || query.isEmpty) return path;

    final sorted = Map.fromEntries(
      query.entries.map((e) => MapEntry(e.key, e.value)).toList()
        ..sort((a, b) => a.key.compareTo(b.key)),
    );

    return '$path?${jsonEncode(sorted)}';
  }

  static Duration _ttlFor(String path) {
    for (final entry in ttlOverrides.entries) {
      if (path.startsWith(entry.key)) return entry.value;
    }
    return defaultTtl;
  }

  /// قراءة مدخل صالح، أو null إن لم يوجد أو انتهى عمره.
  static Object? read(String path, Map<String, dynamic>? query) {
    final entry = _store[keyFor(path, query)];
    if (entry == null) return null;

    if (DateTime.now().difference(entry.fetchedAt) > _ttlFor(path)) {
      _store.remove(keyFor(path, query));
      return null;
    }

    return entry.data;
  }

  /// هل المدخل موجود لكنه قديم؟ يُستخدم للتحديث في الخلفية لاحقاً.
  static bool isStale(String path, Map<String, dynamic>? query) {
    final entry = _store[keyFor(path, query)];
    if (entry == null) return true;
    return DateTime.now().difference(entry.fetchedAt) > _ttlFor(path);
  }

  static void write(String path, Map<String, dynamic>? query, Object? data) {
    if (data == null) return;
    _store[keyFor(path, query)] = _CacheEntry(data, DateTime.now());
  }

  /// إبطال كل ما يبدأ بمسار معيّن — يُستدعى بعد أي كتابة.
  static void invalidatePrefix(String prefix) {
    _store.removeWhere((key, _) => key.startsWith(prefix));
  }

  /// مسح الكاش بالكامل — عند تسجيل الدخول أو الخروج حتى لا تتسرّب
  /// بيانات مستخدم إلى آخر.
  static void clear() => _store.clear();

  /// عدد المدخلات المخزّنة حالياً (للاختبارات والتشخيص).
  static int get size => _store.length;
}

class _CacheEntry {
  final Object? data;
  final DateTime fetchedAt;

  const _CacheEntry(this.data, this.fetchedAt);
}
