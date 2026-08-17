import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// واجهة تخزين آمنة للتوكن والدور وتوكن FCM.
///
/// Arabic: تُستخدم لعزل تفاصيل التخزين (Secure Storage) عن بقية التطبيق.
/// EN: Abstraction over secure storage for auth token, role, and FCM token.
abstract class TokenStorage {
  /// حفظ توكن المصادقة (Bearer token) محلياً.
  /// EN: Persist auth bearer token.
  Future<void> saveToken(String token);

  /// قراءة توكن المصادقة إن وجد.
  /// EN: Read auth bearer token if present.
  Future<String?> getToken();

  /// حذف التوكن وما يرتبط به (الدور + FCM token).
  /// EN: Deletes token and related keys (role + FCM token).
  Future<void> deleteToken();

  /// هل يوجد توكن محفوظ وصالح (غير فارغ)؟
  /// EN: Whether a non-empty token exists.
  Future<bool> hasToken();

  /// حفظ دور المستخدم القادم من السيرفر لاختيار الواجهة الرئيسية.
  /// EN: Persist user role for initial navigation.
  Future<void> saveRole(String role);

  /// قراءة الدور إن وجد.
  /// EN: Read stored role.
  Future<String?> getRole();

  /// حفظ توكن Firebase Cloud Messaging محلياً.
  /// EN: Persist FCM token.
  Future<void> saveFcmToken(String token);

  /// قراءة توكن FCM إن وجد.
  /// EN: Read stored FCM token.
  Future<String?> getFcmToken();

  /// حذف توكن FCM فقط.
  /// EN: Deletes stored FCM token.
  Future<void> deleteFcmToken();

  /// حفظ معرّف المستخدم الحالي لعزل بيانات العمل دون إنترنت.
  ///
  /// Arabic: طابور المزامنة والكاش يُفهرسان بهذا المفتاح، فلا يرى مستخدم بيانات
  /// مستخدم آخر على الجهاز نفسه، ويستعيد كلٌّ منهم عمله المعلّق عند إعادة الدخول.
  /// EN: Persists the current user id used to scope the offline queue and cache.
  Future<void> saveUserKey(String userKey);

  /// قراءة معرّف المستخدم الحالي إن وُجد.
  /// EN: Reads the current offline scope key.
  Future<String?> getUserKey();
}

/// تطبيق فعلي للتخزين باستخدام `flutter_secure_storage`.
///
/// Arabic: يطبّق مفاتيح ثابتة لضمان اتساق التخزين عبر التطبيق.
/// EN: Secure storage implementation using fixed key names.
class SecureTokenStorage implements TokenStorage {
  final FlutterSecureStorage _storage;

  static const String _tokenKey = 'sanctum_token';
  static const String _roleKey = 'app_role';
  static const String _fcmKey = 'fcm_token';
  static const String _userKey = 'offline_user_key';

  const SecureTokenStorage({FlutterSecureStorage? storage})
      : _storage = storage ?? const FlutterSecureStorage();

  @override
  Future<void> saveToken(String token) async {
    await _storage.write(key: _tokenKey, value: token);
  }

  @override
  Future<String?> getToken() async {
    return _storage.read(key: _tokenKey);
  }

  @override
  Future<void> deleteToken() async {
    await _storage.delete(key: _tokenKey);
    await _storage.delete(key: _roleKey);
    await _storage.delete(key: _fcmKey);
    // يُمسح مفتاح النطاق فقط، أما صفوف الطابور المفهرسة به فتبقى في قاعدة البيانات
    // ليستعيدها المستخدم عند إعادة الدخول — الخروج يجب ألّا يمحو عملاً لم يُرسل بعد.
    await _storage.delete(key: _userKey);
  }

  @override
  Future<bool> hasToken() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }

  @override
  Future<void> saveRole(String role) async {
    await _storage.write(key: _roleKey, value: role);
  }

  @override
  Future<String?> getRole() async {
    return _storage.read(key: _roleKey);
  }

  @override
  Future<void> saveFcmToken(String token) async {
    await _storage.write(key: _fcmKey, value: token);
  }

  @override
  Future<String?> getFcmToken() async {
    return _storage.read(key: _fcmKey);
  }

  @override
  Future<void> deleteFcmToken() async {
    await _storage.delete(key: _fcmKey);
  }

  @override
  Future<void> saveUserKey(String userKey) async {
    await _storage.write(key: _userKey, value: userKey);
  }

  @override
  Future<String?> getUserKey() async {
    return _storage.read(key: _userKey);
  }
}
