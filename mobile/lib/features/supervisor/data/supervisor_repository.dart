import '../../../core/constants/api_constants.dart';
import '../../../services/api/api_client.dart';
import 'supervisor_models.dart';

// --- Repository Architecture ---

/// واجهة مستودع بيانات المشرف (Interface)
/// Arabic: تحدد العقود والدوال الخاصة بقسم المشرف لضمان سهولة الاختبار وفصل التنفيذ.
/// EN: Interface for supervisor API endpoints.
abstract class SupervisorRepository {
  Future<Map<String, dynamic>> fetchMe();
  Future<SupervisorStats> fetchStats();
  Future<TeacherDetail> fetchTeacherDetail(int teacherId);
  Future<HalaqahDaily> fetchHalaqahDaily(int halaqahId, {String? date});
  Future<VisitResponse> storeVisit(VisitRequest request);
  Future<MyVisitsPageData> fetchMyVisits({int page = 1});
  Future<AttendanceStats> fetchAttendanceStats({int days = 7});
  Future<List<Map<String, dynamic>>> fetchCenters();
  Future<List<Map<String, dynamic>>> fetchTeachers();
}

/// تنفيذ مستودع بيانات المشرف (Implementation)
/// Arabic: يوفّر التنفيذ الفعلي لدوال استدعاء نقاط نهاية المشرف.
/// EN: Concrete implementation for supervisor API endpoints.
class SupervisorRepositoryImpl implements SupervisorRepository {
  final ApiClient _client;

  const SupervisorRepositoryImpl({required ApiClient apiClient})
      : _client = apiClient;

  @override
  Future<Map<String, dynamic>> fetchMe() async {
    final res = await _client.get(ApiConstants.supervisorMeEndpoint);
    return Map<String, dynamic>.from(res.data as Map);
  }

  @override
  Future<SupervisorStats> fetchStats() async {
    final res = await _client.get(ApiConstants.supervisorStats);
    return SupervisorStats.fromJson(Map<String, dynamic>.from(res.data as Map));
  }

  @override
  Future<TeacherDetail> fetchTeacherDetail(int teacherId) async {
    final res = await _client.get('${ApiConstants.supervisorTeacherDetail}$teacherId');
    return TeacherDetail.fromJson(Map<String, dynamic>.from(res.data as Map));
  }

  @override
  Future<HalaqahDaily> fetchHalaqahDaily(int halaqahId, {String? date}) async {
    final res = await _client.get(
      '${ApiConstants.supervisorHalaqahDaily}$halaqahId/daily',
      queryParameters: date != null ? {'date': date} : null,
    );
    return HalaqahDaily.fromJson(Map<String, dynamic>.from(res.data as Map));
  }

  @override
  Future<VisitResponse> storeVisit(VisitRequest request) async {
    final res = await _client.post(
      ApiConstants.supervisorStoreVisit,
      data: request.toJson(),
    );
    return VisitResponse.fromJson(Map<String, dynamic>.from(res.data as Map));
  }

  @override
  Future<MyVisitsPageData> fetchMyVisits({int page = 1}) async {
    final res = await _client.get(
      ApiConstants.supervisorMyVisits,
      queryParameters: {'page': page},
    );
    final map = Map<String, dynamic>.from(res.data as Map);
    final list = map['data'] as List<dynamic>;
    return MyVisitsPageData(
      items: list
          .map((e) => VisitItem.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList(),
      total: map['total'] as int,
      currentPage: map['current_page'] as int,
      lastPage: map['last_page'] as int,
    );
  }

  @override
  Future<AttendanceStats> fetchAttendanceStats({int days = 7}) async {
    final res = await _client.get(
      ApiConstants.supervisorAttendance,
      queryParameters: {'days': days},
    );
    return AttendanceStats.fromJson(Map<String, dynamic>.from(res.data as Map));
  }

  @override
  Future<List<Map<String, dynamic>>> fetchCenters() async {
    final res = await _client.get(ApiConstants.supervisorCentersEndpoint);
    final data = res.data as Map<String, dynamic>;
    final list = data['data'] as List<dynamic>? ?? [];
    return list.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }

  @override
  Future<List<Map<String, dynamic>>> fetchTeachers() async {
    final res = await _client.get(ApiConstants.supervisorTeachersEndpoint);
    final data = res.data as Map<String, dynamic>;
    final list = data['data'] as List<dynamic>? ?? [];
    return list.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }
}