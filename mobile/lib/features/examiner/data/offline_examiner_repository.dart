import '../../../core/constants/api_constants.dart';
import '../../../core/offline/offline_gateway.dart';
import 'examiner_models.dart';
import 'examiner_repository.dart';

/// مغلّف يضيف الكتابة المؤجلة لتسجيل نتائج الاختبارات.
///
/// Arabic: العملية آمنة للتكرار على الخادم أصلاً لأنها `updateOrCreate` بمفتاح
/// التعيين، فلا حاجة لمعرّف عميل. المهم هنا إرسال `client_recorded_at` ليُسجَّل وقت
/// الاختبار الفعلي في الميدان لا لحظة وصول المزامنة — وإلا ظهرت اختبارات الصباح في
/// التقارير مساءً.
/// EN: Deferred writes for exam results; the client timestamp keeps `tested_at`
/// anchored to the field, not to sync time.
class OfflineExaminerRepository implements ExaminerRepository {
  final ExaminerRepository _inner;
  final OfflineGateway _gateway;

  const OfflineExaminerRepository({
    required ExaminerRepository inner,
    required OfflineGateway gateway,
  })  : _inner = inner,
        _gateway = gateway;

  /// مفتاح دمج نتيجة طالب في اختبار: تصحيح الدرجة قبل المزامنة يستبدل القيمة السابقة.
  static String resultDedupeKey(int testId, int studentId) =>
      'examiner:result:$testId:$studentId';

  @override
  Future<TestResultResponse> submitResult(TestResultRequest request) async {
    final body = await _gateway.write(
      endpoint: ApiConstants.examinerStoreResult,
      payload: request.toJson(),
      dedupeKey: resultDedupeKey(request.testId, request.studentId),
      label: 'نتيجة اختبار — ${request.testedSurah}',
    );

    return TestResultResponse.fromJson(body);
  }

  // --- قراءات تمرّ كما هي (الكاش مطبَّق في CachingApiClient) ---

  @override
  Future<ExaminerStats> fetchStats() => _inner.fetchStats();

  @override
  Future<Map<String, dynamic>> fetchMe() => _inner.fetchMe();

  @override
  Future<List<Map<String, dynamic>>> fetchTests() => _inner.fetchTests();

  @override
  Future<List<Map<String, dynamic>>> fetchAssignmentsForTest(int testId) =>
      _inner.fetchAssignmentsForTest(testId);

  @override
  Future<StudentDetail> fetchStudentDetail(int studentId) => _inner.fetchStudentDetail(studentId);

  @override
  Future<DailySummary> fetchDailySummary({String? date}) => _inner.fetchDailySummary(date: date);

  @override
  Future<List<Map<String, dynamic>>> fetchTestAssignments() => _inner.fetchTestAssignments();

  @override
  Future<List<Map<String, dynamic>>> fetchTestResults() => _inner.fetchTestResults();
}
