import '../../../core/constants/api_constants.dart';
import '../../../core/errors/api_exception.dart';
import '../../../services/api/api_client.dart';
import '../../../storage/token_storage.dart';

/// نتيجة تسجيل الدخول من API.
///
/// Arabic: تمثل بنية الاستجابة المتوقعة من `/api/login`.
/// EN: Represents the expected payload returned by the login endpoint.
class LoginResult {
  final String token;
  final String name;
  final String email;
  final int userId;
  final String role;

  const LoginResult({
    required this.token,
    required this.name,
    required this.email,
    required this.userId,
    required this.role,
  });

  factory LoginResult.fromJson(Map<String, dynamic> json) {
    final user = json['user'] as Map<String, dynamic>;
    return LoginResult(
      token: json['token'] as String,
      name: user['name'] as String,
      email: user['email'] as String,
      userId: user['id'] as int,
      role: user['role'] as String,
    );
  }
}

/// معلومات جلسة المستخدم (Session) عند وجود توكن بدون دور محفوظ.
///
/// Arabic: تُستخدم لترقية تجربة المستخدم عند تحديث التطبيق أو حذف بيانات الدور محلياً.
/// EN: Used to fetch the role when only a token exists (e.g., after upgrades).
class SessionUser {
  final int id;
  final String name;
  final String email;
  final String role;

  const SessionUser({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
  });

  factory SessionUser.fromJson(Map<String, dynamic> json) {
    final user = json['user'] as Map<String, dynamic>;
    return SessionUser(
      id: user['id'] as int,
      name: user['name'] as String,
      email: user['email'] as String,
      role: user['role'] as String,
    );
  }
}

/// مستودع المصادقة للجوال (Login/Session/Logout + مزامنة FCM).
///
/// Arabic: يعزل تفاصيل استدعاءات API والتخزين المحلي عن الواجهات.
/// EN: Authentication repository for mobile (API calls + local persistence).
class AuthRepository {
  final ApiClient _apiClient;
  final TokenStorage _tokenStorage;

  const AuthRepository({
    required ApiClient apiClient,
    required TokenStorage tokenStorage,
  })  : _apiClient = apiClient,
        _tokenStorage = tokenStorage;

  /// تسجيل الدخول واستلام التوكن وتخزينه محلياً.
  ///
  /// Arabic: يرسل أيضاً `fcm_token` إن كان متوفراً لضمان استقبال الإشعارات.
  /// EN: Logs in, persists token/role, and includes FCM token when available.
  Future<LoginResult> login({
    required String email,
    required String password,
  }) async {
    final fcm = await _tokenStorage.getFcmToken();
    final response = await _apiClient.post(
      ApiConstants.loginEndpoint,
      data: {
        'email': email,
        'password': password,
        if (fcm != null && fcm.isNotEmpty) 'fcm_token': fcm,
      },
    );

    final result = LoginResult.fromJson(
      response.data as Map<String, dynamic>,
    );

    await _tokenStorage.saveToken(result.token);
    await _tokenStorage.saveRole(result.role);
    await _tokenStorage.saveUserKey(result.userId.toString());
    await syncFcmTokenToServer();
    return result;
  }

  /// بعد تسجيل الدخول أو تحديث التوكن محلياً
  ///
  /// Arabic: يحاول إرسال توكن FCM إلى السيرفر بأفضل جهد (best-effort) بدون إزعاج المستخدم.
  /// EN: Best-effort sync of local FCM token to the server.
  Future<void> syncFcmTokenToServer() async {
    // نقطة `/api/fcm-token` محميّة؛ استدعاؤها قبل تسجيل الدخول يعطي 401 بلا فائدة.
    if (!await _tokenStorage.hasToken()) {
      return;
    }

    final fcm = await _tokenStorage.getFcmToken();
    if (fcm == null || fcm.isEmpty) {
      return;
    }
    try {
      await _apiClient.post(
        ApiConstants.fcmTokenEndpoint,
        data: {'fcm_token': fcm},
      );
    } on ApiException {
      // تجاهل — يُعاد المحاولة لاحقاً
    }
  }

  /// عند وجود توكن دون دور محفوظ (ترقية من إصدار قديم).
  ///
  /// Arabic: يستدعي `/api/session` ليجلب الدور ثم يخزنه محلياً.
  /// EN: Fetches session info from the backend to restore the role.
  Future<SessionUser?> fetchSession() async {
    final response = await _apiClient.get(ApiConstants.sessionEndpoint);
    final data = response.data as Map<String, dynamic>;
    final session = SessionUser.fromJson(data);
    await _tokenStorage.saveRole(session.role);
    await _tokenStorage.saveUserKey(session.id.toString());
    await syncFcmTokenToServer();
    return session;
  }

  /// تسجيل الخروج.
  ///
  /// Arabic: يحاول إبطال التوكن على السيرفر، ثم يحذف التوكن محلياً مهما كانت النتيجة.
  /// EN: Best-effort server logout, always clears local auth state.
  Future<void> logout() async {
    try {
      await _apiClient.post(ApiConstants.logoutEndpoint);
    } on ApiException {
      // best-effort logout
    } finally {
      await _tokenStorage.deleteToken();
    }
  }
}
