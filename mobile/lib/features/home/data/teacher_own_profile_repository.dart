import 'dart:convert' show base64Encode;
import 'package:image_picker/image_picker.dart';

import '../../../core/constants/api_constants.dart';
import '../../../services/api/api_client.dart';

// --- Repository Architecture ---

/// واجهة المستودع (Interface) لضمان سهولة الاختبار وفصل التنفيذ
abstract class TeacherOwnProfileRepository {
  Future<Map<String, dynamic>> fetchProfile();
  Future<Map<String, dynamic>> updatePhoto(XFile file);
}

/// تنفيذ المستودع (Implementation)
class TeacherOwnProfileRepositoryImpl implements TeacherOwnProfileRepository {
  final ApiClient _apiClient;

  const TeacherOwnProfileRepositoryImpl({required ApiClient apiClient})
    : _apiClient = apiClient;

  @override
  Future<Map<String, dynamic>> fetchProfile() async {
    // جلب بيانات الملف الشخصي للمعلم (تم تعديل الرابط هنا)
    final res = await _apiClient.get(ApiConstants.teacherMeEndpoint);
    return res.data as Map<String, dynamic>;
  }

  @override
  Future<Map<String, dynamic>> updatePhoto(XFile file) async {
    final bytes = await file.readAsBytes();
    if (bytes.isEmpty) {
      throw StateError('empty_image');
    }

    final res = await _apiClient.post(
      ApiConstants.teacherProfileEndpoint,
      data: {'photo_base64': base64Encode(bytes)},
    );
    return res.data as Map<String, dynamic>;
  }
}
