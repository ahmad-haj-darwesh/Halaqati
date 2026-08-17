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
    final list = (res.data as List<dynamic>);
    return list.map((e) => StudentListItemDto.fromJson(e as Map<String, dynamic>)).toList();
  }
}