import '../../../core/offline/offline_gateway.dart';
import 'teacher_daily_repository.dart';

/// مغلّف يضيف الكتابة المؤجلة لمستودع السجل اليومي للمعلم.
///
/// Arabic: القراءة تمرّ عبر `CachingApiClient` تلقائياً، فلا يتبقى هنا سوى الحفظ.
/// هذه أهم شاشة للعمل دون شبكة: المعلّم غالباً في مسجد بتغطية ضعيفة، وفقدان تسجيل
/// حلقة كاملة غير مقبول.
/// EN: Adds deferred writes to the teacher daily repository (reads are cached at
/// the HTTP layer).
class OfflineTeacherDailyRepository implements TeacherDailyRepository {
  final TeacherDailyRepository _inner;
  final OfflineGateway _gateway;

  const OfflineTeacherDailyRepository({
    required TeacherDailyRepository inner,
    required OfflineGateway gateway,
  })  : _inner = inner,
        _gateway = gateway;

  /// مفتاح دمج سجلات يوم واحد.
  ///
  /// Arabic: ضغط المعلّم «حفظ الكل» خمس مرات بلا شبكة يجب أن ينتج عملية واحدة تحمل
  /// آخر القيم، لا خمس عمليات متعاقبة تُرسل نفس البيانات خمس مرات.
  /// EN: Repeated saves for one day coalesce into a single queued write.
  static String dailyDedupeKey(String date) => 'teacher:daily:$date';

  static const String upsertEndpoint = '/teacher/daily-records/upsert';

  @override
  Future<HalaqahTodayResponseDto> getToday({String? date}) => _inner.getToday(date: date);

  @override
  Future<MonthlyReportDto> getMonthlyReport({required String month}) =>
      _inner.getMonthlyReport(month: month);

  @override
  Future<void> upsertDailyRecords({
    required String date,
    required List<StudentTodayDto> students,
  }) {
    return _gateway.write(
      endpoint: upsertEndpoint,
      payload: {
        'date': date,
        'records': students.map((s) => s.toUpsertRecordJson()).toList(),
      },
      dedupeKey: dailyDedupeKey(date),
      label: 'سجل يوم $date — ${students.length} طالب',
    );
  }

  /// هل ما زال سجل هذا اليوم بانتظار الإرسال؟
  Future<bool> hasPendingFor(String date) => _gateway.hasPending(dailyDedupeKey(date));
}
