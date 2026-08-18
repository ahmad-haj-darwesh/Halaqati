import 'package:dio/dio.dart';
import '../../core/constants/api_constants.dart';
import '../../core/errors/api_exception.dart';
import '../../storage/token_storage.dart';

/// عميل HTTP بسيط يعتمد على Dio للتواصل مع Laravel API.
///
/// Arabic: يضبط `baseUrl` والمهل الافتراضية ويضيف Interceptors لتحديد `Content-Type`
/// وإرفاق Authorization header عند وجود توكن.
/// EN: A thin Dio wrapper configured for the backend API with auth interceptors.
class ApiClient {
  final Dio _dio;
  final TokenStorage _tokenStorage;

  /// إنشاء عميل API.
  ///
  /// Arabic: يمكن تمرير Dio مخصص للاختبارات، لكن الافتراضي يضبط الخيارات العامة.
  /// EN: Builds the client; accepts an optional Dio for testing.
  ApiClient({Dio? dio, required TokenStorage tokenStorage})
      : _dio = dio ?? Dio(),
        _tokenStorage = tokenStorage {
    _dio.options = BaseOptions(
      baseUrl: ApiConstants.baseUrl,
      connectTimeout: ApiConstants.connectTimeout,
      receiveTimeout: ApiConstants.receiveTimeout,
      headers: const {'Accept': 'application/json'},
    );
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) {
          final data = options.data;
          if (data is! FormData &&
              (data is Map || data is List)) {
            options.headers['Content-Type'] = Headers.jsonContentType;
          }
          handler.next(options);
        },
      ),
    );
    _dio.interceptors.add(_AuthInterceptor(_tokenStorage));
  }

  /// تنفيذ طلب GET.
  /// EN: Perform a GET request.
  Future<Response> get(String path, {Map<String, dynamic>? queryParameters}) async {
    try {
      return await _dio.get(path, queryParameters: queryParameters);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// تنفيذ طلب POST.
  /// EN: Perform a POST request.
  Future<Response> post(String path, {dynamic data}) async {
    try {
      return await _dio.post(path, data: data);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// تنفيذ طلب PUT.
  /// EN: Perform a PUT request.
  Future<Response> put(String path, {dynamic data}) async {
    try {
      return await _dio.put(path, data: data);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// تحويل أخطاء Dio إلى `ApiException` برسائل عربية مناسبة.
  ///
  /// Arabic: يفضّل الرسائل القادمة من السيرفر (Validation errors) عند توفرها.
  /// EN: Maps Dio errors into a domain exception with user-friendly messages.
  ApiException _handleError(DioException e) {
    final statusCode = e.response?.statusCode;
    String message;

    switch (e.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.receiveTimeout:
        message = 'انتهت مهلة الاتصال. تحقق من الإنترنت.';
      case DioExceptionType.badResponse:
        message = _messageFromErrorBody(e.response?.data, statusCode);
      case DioExceptionType.connectionError:
        message = 'تعذّر الاتصال بالخادم.';
      default:
        message = 'خطأ غير متوقع.';
    }

    return ApiException(message: message, statusCode: statusCode);
  }

  /// استخراج رسالة مفهومة من جسم استجابة الخطأ.
  ///
  /// Arabic: الجسم ليس دائماً خريطة JSON — قد يعيد وسيط في الشبكة (بوابة WiFi
  /// أو صفحة خطأ من الاستضافة) صفحة HTML نصية. وفهرسة نص بمفتاح نصي تُطلق
  /// `TypeError` يتجاوز معالجة الأخطاء نفسها ويصل للمستخدم كرسالة تقنية، لذا
  /// يُفحص النوع قبل أي فهرسة، وتُحمى `first` من الحالات الفارغة.
  /// EN: Error bodies are not always JSON maps (HTML portals, host error pages);
  /// guard the type before indexing and never call `first` on empty collections.
  static String _messageFromErrorBody(dynamic data, int? statusCode) {
    if (data is Map) {
      final errors = data['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List) {
          if (first.isNotEmpty) return first.first.toString();
        } else if (first != null) {
          return first.toString();
        }
      }

      final serverMessage = data['message'];
      if (serverMessage is String && serverMessage.isNotEmpty) {
        return serverMessage;
      }
      if (statusCode == 401) return 'انتهت الجلسة. سجّل الدخول من جديد.';
      if (statusCode == 403) return 'لا تملك صلاحية لهذا الإجراء.';
      if (statusCode != null && statusCode >= 500) {
        return 'الخادم غير متاح حالياً. حاول لاحقاً. ($statusCode)';
      }

      return 'تعذّر إتمام الطلب من الخادم. ($statusCode)';
    }

    // جسم غير JSON يعني أن الطلب لم يصل إلى الـ API أصلاً، بل ردّ عليه وسيط في
    // الطريق (جدار حماية الاستضافة، بوابة WiFi، أو مزوّد الخدمة) بصفحة HTML.
    // رسالة الصلاحيات هنا مضلِّلة: السبب في الشبكة لا في حساب المستخدم.
    return statusCode != null
        ? 'تعذّر الوصول إلى الخادم — حُجب الطلب في الطريق ($statusCode). '
            'جرّب تعطيل VPN أو تبديل الشبكة.'
        : 'تعذّر الوصول إلى الخادم. تحقق من اتصال الإنترنت.';
  }
}

class _AuthInterceptor extends Interceptor {
  final TokenStorage _tokenStorage;

  _AuthInterceptor(this._tokenStorage);

  /// إرفاق توكن المصادقة تلقائياً مع كل طلب.
  ///
  /// Arabic: يستخدم `Bearer <token>` عند وجود توكن محفوظ.
  /// EN: Adds `Authorization: Bearer <token>` when available.
  @override
  Future<void> onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    final token = await _tokenStorage.getToken();
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }
}
