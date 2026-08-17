import '../../../core/constants/api_constants.dart';
import '../../../services/api/api_client.dart';
import 'examiner_models.dart';

// --- Repository Architecture ---

/// واجهة مستودع بيانات المختبر (Interface)
/// Arabic: تحدد العقود والدوال الخاصة بقسم المختبر لضمان سهولة الاختبار.
/// EN: Interface for examiner API endpoints.
abstract class ExaminerRepository {
  Future<ExaminerStats> fetchStats();
  Future<Map<String, dynamic>> fetchMe();
  Future<List<Map<String, dynamic>>> fetchTests();
  Future<List<Map<String, dynamic>>> fetchAssignmentsForTest(int testId);
  Future<StudentDetail> fetchStudentDetail(int studentId);
  Future<TestResultResponse> submitResult(TestResultRequest request);
  Future<DailySummary> fetchDailySummary({String? date});
  Future<List<Map<String, dynamic>>> fetchTestAssignments();
  Future<List<Map<String, dynamic>>> fetchTestResults();
}

/// تنفيذ مستودع بيانات المختبر (Implementation)
/// Arabic: يوفّر التنفيذ الفعلي لدوال استدعاء نقاط نهاية المختبر.
/// EN: Concrete implementation for examiner API endpoints.
class ExaminerRepositoryImpl implements ExaminerRepository {
  final ApiClient _client;

  const ExaminerRepositoryImpl({required ApiClient apiClient})
      : _client = apiClient;

  @override
  Future<ExaminerStats> fetchStats() async {
    final res = await _client.get(ApiConstants.examinerStats);
    return ExaminerStats.fromJson(Map<String, dynamic>.from(res.data as Map));
  }

  @override
  Future<Map<String, dynamic>> fetchMe() async {
    final res = await _client.get(ApiConstants.examinerMeEndpoint);
    return Map<String, dynamic>.from(res.data as Map);
  }

  @override
  Future<List<Map<String, dynamic>>> fetchTests() async {
    final res = await _client.get(ApiConstants.examinerTestsEndpoint);
    final data = res.data as Map<String, dynamic>;
    final list = data['data'] as List<dynamic>? ?? [];
    return list.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }

  @override
  Future<List<Map<String, dynamic>>> fetchAssignmentsForTest(int testId) async {
    final res = await _client.get(ApiConstants.examinerAssignmentsForTest(testId));
    final data = res.data as Map<String, dynamic>;
    final list = data['data'] as List<dynamic>? ?? [];
    return list.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }

  @override
  Future<StudentDetail> fetchStudentDetail(int studentId) async {
    final res = await _client.get('${ApiConstants.examinerStudentDetailPrefix}$studentId');
    return StudentDetail.fromJson(Map<String, dynamic>.from(res.data as Map));
  }

  @override
  Future<TestResultResponse> submitResult(TestResultRequest request) async {
    final res = await _client.post(
      ApiConstants.examinerStoreResult,
      data: request.toJson(),
    );
    return TestResultResponse.fromJson(Map<String, dynamic>.from(res.data as Map));
  }

  @override
  Future<DailySummary> fetchDailySummary({String? date}) async {
    final res = await _client.get(
      ApiConstants.examinerDailySummary,
      queryParameters: date != null ? {'date': date} : null,
    );
    return DailySummary.fromJson(Map<String, dynamic>.from(res.data as Map));
  }

  @override
  Future<List<Map<String, dynamic>>> fetchTestAssignments() async {
    final res = await _client.get(ApiConstants.examinerTestAssignmentsEndpoint);
    final data = res.data as Map<String, dynamic>;
    final list = data['data'] as List<dynamic>? ?? [];
    return list.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }

  @override
  Future<List<Map<String, dynamic>>> fetchTestResults() async {
    final res = await _client.get(ApiConstants.examinerTestResultsEndpoint);
    final data = res.data as Map<String, dynamic>;
    final list = data['data'] as List<dynamic>? ?? [];
    return list.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }
}