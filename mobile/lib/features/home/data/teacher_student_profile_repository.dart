import 'dart:convert' show base64Encode;
import 'package:image_picker/image_picker.dart';

import '../../../core/constants/api_constants.dart';
import '../../../services/api/api_client.dart';

// --- DTOs (لا تغيير في منطقها) ---

class StudentProfileDto {
  final int id;
  final String fullName;
  final String gender;
  final String? birthDate;
  final String? guardianName;
  final String? guardianPhone;
  final String? nationalId;
  final String? notes;
  final bool isActive;
  final String? photoPath;
  final String? photoUrl;
  final bool profileLocked;
  final bool teacherMayEditProfile;

  const StudentProfileDto({
    required this.id,
    required this.fullName,
    required this.gender,
    this.birthDate,
    this.guardianName,
    this.guardianPhone,
    this.nationalId,
    this.notes,
    required this.isActive,
    this.photoPath,
    this.photoUrl,
    required this.profileLocked,
    required this.teacherMayEditProfile,
  });

  factory StudentProfileDto.fromJson(Map<String, dynamic> json) {
    return StudentProfileDto(
      id: json['id'] as int,
      fullName: json['full_name'] as String,
      gender: json['gender'] as String,
      birthDate: json['birth_date'] as String?,
      guardianName: json['guardian_name'] as String?,
      guardianPhone: json['guardian_phone'] as String?,
      nationalId: json['national_id'] as String?,
      notes: json['notes'] as String?,
      isActive: json['is_active'] as bool? ?? true,
      photoPath: json['photo_path'] as String?,
      photoUrl: json['photo_url'] as String?,
      profileLocked: json['profile_locked'] as bool? ?? false,
      teacherMayEditProfile: json['teacher_may_edit_profile'] as bool? ?? true,
    );
  }
}

class StudentProfileScreenState {
  final StudentProfileDto student;
  final bool canEdit;
  final bool canSubmit;
  final Map<String, dynamic>? pendingSubmission;

  const StudentProfileScreenState({
    required this.student,
    required this.canEdit,
    required this.canSubmit,
    this.pendingSubmission,
  });

  factory StudentProfileScreenState.fromJson(Map<String, dynamic> json) {
    return StudentProfileScreenState(
      student: StudentProfileDto.fromJson(json['student'] as Map<String, dynamic>),
      canEdit: json['can_edit'] as bool? ?? false,
      canSubmit: json['can_submit'] as bool? ?? false,
      pendingSubmission: json['pending_submission'] as Map<String, dynamic>?,
    );
  }
}

// --- Repository Architecture ---

/// واجهة المستودع لضمان سهولة الاختبار وفصل التنفيذ
abstract class TeacherStudentProfileRepository {
  Future<StudentProfileScreenState> fetch(int studentId);
  Future<StudentProfileDto> updateProfile({
    required int studentId,
    required String fullName,
    required String gender,
    String? birthDate,
    String? guardianName,
    String? guardianPhone,
    String? nationalId,
    String? notes,
    XFile? newPhoto,
  });
  Future<void> submitForReview(int studentId);
}

/// التنفيذ الفعلي (Implementation)
class TeacherStudentProfileRepositoryImpl implements TeacherStudentProfileRepository {
  final ApiClient _apiClient;

  const TeacherStudentProfileRepositoryImpl({required ApiClient apiClient})
      : _apiClient = apiClient;

  @override
  Future<StudentProfileScreenState> fetch(int studentId) async {
    final res = await _apiClient.get(ApiConstants.teacherStudentProfile(studentId));
    return StudentProfileScreenState.fromJson(res.data as Map<String, dynamic>);
  }

  @override
  Future<StudentProfileDto> updateProfile({
    required int studentId,
    required String fullName,
    required String gender,
    String? birthDate,
    String? guardianName,
    String? guardianPhone,
    String? nationalId,
    String? notes,
    XFile? newPhoto,
  }) async {
    final body = <String, dynamic>{
      'full_name': fullName,
      'gender': gender,
      if (birthDate != null && birthDate.isNotEmpty) 'birth_date': birthDate,
      if (guardianName != null) 'guardian_name': guardianName,
      if (guardianPhone != null) 'guardian_phone': guardianPhone,
      if (nationalId != null) 'national_id': nationalId,
      if (notes != null) 'notes': notes,
    };

    if (newPhoto != null) {
      final bytes = await newPhoto.readAsBytes();
      if (bytes.isNotEmpty) body['photo_base64'] = base64Encode(bytes);
    }

    final res = await _apiClient.put(ApiConstants.teacherStudentProfile(studentId), data: body);
    return StudentProfileDto.fromJson((res.data as Map<String, dynamic>)['student'] as Map<String, dynamic>);
  }

  @override
  Future<void> submitForReview(int studentId) async {
    await _apiClient.post(ApiConstants.teacherStudentProfileSubmit(studentId));
  }
}