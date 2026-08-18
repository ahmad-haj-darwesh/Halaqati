import '../../../core/constants/api_constants.dart';
import '../../../services/api/api_client.dart';

// --- DTO ---
class StudentListItemDto {
  final int id;
  final String fullName;
  final bool isActive;
  final String? photoUrl;
  final Map<String, dynamic>? enrollment;

  const StudentListItemDto({
    required this.id,
    required this.fullName,
    required this.isActive,
    this.photoUrl,
    this.enrollment,
  });

  factory StudentListItemDto.fromJson(Map<String, dynamic> json) {
    return StudentListItemDto(
      id: json['id'] as int,
      fullName: json['full_name'] as String,
      isActive: json['is_active'] as bool? ?? true,
      photoUrl: json['photo_url'] as String?,
      enrollment: json['enrollment'] as Map<String, dynamic>?,
    );
  }
}

// --- Repository Architecture ---

abstract class TeacherStudentsRepository {
  Future<List<StudentListItemDto>> fetchAll();
}

class TeacherStudentsRepositoryImpl implements TeacherStudentsRepository {
  final ApiClient _apiClient;

  const TeacherStudentsRepositoryImpl({required ApiClient apiClient})
      : _apiClient = apiClient;

  @override
  Future<List<StudentListItemDto>> fetchAll() async {
    final res = await _apiClient.get(ApiConstants.teacherStudentsEndpoint);

    // الخادم يغلّف القائمة داخل مفتاح `data` — انظر TeacherStudentsController::index.
    // نقبل الشكلين (مغلّف أو قائمة مباشرة) حتى لا ينهار التطبيق إن تغيّر العقد.
    final raw = res.data;
    final list = raw is List
        ? raw
        : (raw is Map ? (raw['data'] as List<dynamic>? ?? const []) : const []);

    return list
        .whereType<Map>()
        .map((e) => StudentListItemDto.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }
}