/// نماذج طبقة العمل دون إنترنت: عنصر الطابور وحالة المزامنة.
///
/// Arabic: تُبقي التمثيل بسيطاً (خرائط JSON) لأن الطابور يخزّن حمولات API كما هي،
/// فلا حاجة لتوليد كود ولا لربط كل مسار بنموذج مستقل.
/// EN: Offline layer models: outbox entry and sync status.
library;

/// حالة عنصر في طابور المزامنة.
enum OutboxStatus {
  /// بانتظار الإرسال أو إعادة المحاولة.
  pending,

  /// رفضه الخادم رفضاً نهائياً (خطأ تحقق/صلاحية) ويحتاج تدخّل المستخدم.
  failed,
}

/// عنصر واحد في طابور الكتابة (Outbox).
///
/// Arabic: يمثّل طلب POST مؤجلاً. `clientRecordedAt` هي لحظة إدخال المستخدم للبيانات
/// أول مرة وتبقى ثابتة حتى لو عُدّل العنصر لاحقاً وهو في الطابور — لأن الخادم يبني
/// عليها نافذة تعديل التقييم، ولأن التقرير يجب أن يعكس وقت الحدث لا وقت الشبكة.
/// EN: A deferred write. `clientRecordedAt` is pinned to the first entry and never
/// advances on edit, since the server derives its edit window from it.
class OutboxEntry {
  final int? id;
  final String userKey;
  final String endpoint;
  final Map<String, dynamic> payload;

  /// مفتاح دمج اختياري: عنصران بنفس المفتاح يتحدان في عنصر واحد بأحدث حمولة.
  ///
  /// Arabic: يمنع تراكم عشرات النسخ حين يضغط المعلّم «حفظ» مراراً لنفس اليوم.
  /// EN: Optional coalescing key so repeated saves collapse into one queued write.
  final String? dedupeKey;

  final DateTime clientRecordedAt;
  final DateTime updatedAt;
  final int attempts;
  final DateTime? nextAttemptAt;
  final OutboxStatus status;
  final String? lastError;

  /// وصف عربي قصير يظهر للمستخدم في شاشة المزامنة.
  final String label;

  const OutboxEntry({
    this.id,
    required this.userKey,
    required this.endpoint,
    required this.payload,
    required this.clientRecordedAt,
    required this.updatedAt,
    required this.label,
    this.dedupeKey,
    this.attempts = 0,
    this.nextAttemptAt,
    this.status = OutboxStatus.pending,
    this.lastError,
  });

  /// هل حان وقت المحاولة التالية؟
  bool isDue(DateTime now) {
    if (status != OutboxStatus.pending) return false;
    final next = nextAttemptAt;
    return next == null || !next.isAfter(now);
  }

  OutboxEntry copyWith({
    int? id,
    Map<String, dynamic>? payload,
    DateTime? updatedAt,
    int? attempts,
    DateTime? nextAttemptAt,
    OutboxStatus? status,
    String? lastError,
    bool clearNextAttempt = false,
    bool clearError = false,
  }) {
    return OutboxEntry(
      id: id ?? this.id,
      userKey: userKey,
      endpoint: endpoint,
      payload: payload ?? this.payload,
      dedupeKey: dedupeKey,
      clientRecordedAt: clientRecordedAt,
      updatedAt: updatedAt ?? this.updatedAt,
      label: label,
      attempts: attempts ?? this.attempts,
      nextAttemptAt: clearNextAttempt ? null : (nextAttemptAt ?? this.nextAttemptAt),
      status: status ?? this.status,
      lastError: clearError ? null : (lastError ?? this.lastError),
    );
  }
}

/// لقطة عن حالة المزامنة تُعرض في الواجهة.
class SyncSnapshot {
  /// هل الجهاز متصل بشبكة (مؤشر وليس ضماناً بالوصول للخادم)؟
  final bool isOnline;

  /// هل تجري دورة مزامنة الآن؟
  final bool isSyncing;

  /// عدد العمليات المنتظرة.
  final int pendingCount;

  /// عدد العمليات المرفوضة نهائياً والمحتاجة تدخّل المستخدم.
  final int failedCount;

  /// آخر مزامنة ناجحة (null إن لم تحدث بعد).
  final DateTime? lastSyncedAt;

  const SyncSnapshot({
    this.isOnline = true,
    this.isSyncing = false,
    this.pendingCount = 0,
    this.failedCount = 0,
    this.lastSyncedAt,
  });

  /// هل هناك عمل غير مُرحَّل للخادم بعد؟
  bool get hasUnsyncedWork => pendingCount > 0 || failedCount > 0;

  SyncSnapshot copyWith({
    bool? isOnline,
    bool? isSyncing,
    int? pendingCount,
    int? failedCount,
    DateTime? lastSyncedAt,
  }) {
    return SyncSnapshot(
      isOnline: isOnline ?? this.isOnline,
      isSyncing: isSyncing ?? this.isSyncing,
      pendingCount: pendingCount ?? this.pendingCount,
      failedCount: failedCount ?? this.failedCount,
      lastSyncedAt: lastSyncedAt ?? this.lastSyncedAt,
    );
  }
}

/// بيانات مقروءة من الكاش مع طابعها الزمني.
///
/// Arabic: الواجهة تحتاج معرفة «متى حُدِّثت هذه البيانات» لتخبر المستخدم بصراحة
/// بدل إيهامه أن ما يراه لحظي.
/// EN: Cached payload plus the time it was fetched, so the UI can say how stale it is.
class CachedPayload<T> {
  final T value;
  final DateTime fetchedAt;

  const CachedPayload({required this.value, required this.fetchedAt});
}
