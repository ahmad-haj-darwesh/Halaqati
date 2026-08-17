/// استثناء نطاق التطبيق لأخطاء HTTP/API بعد تحويلها من Dio.
///
/// Arabic: يحمل رسالة عربية للعرض وأحياناً رمز الحالة HTTP.
/// EN: Domain exception wrapping API failures with a user-facing message.
class ApiException implements Exception {
  final String message;
  final int? statusCode;

  const ApiException({required this.message, this.statusCode});

  @override
  String toString() => 'ApiException($statusCode): $message';
}
