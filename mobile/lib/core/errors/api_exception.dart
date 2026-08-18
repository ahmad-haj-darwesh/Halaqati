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

/// تحويل أي خطأ إلى رسالة صالحة للعرض للمستخدم.
///
/// Arabic: `ApiException` يحمل رسالة عربية جاهزة، أما بقية الأخطاء (مثل
/// `TypeError` أو أخطاء قاعدة البيانات المحلية) فرسائلها تقنية بالإنجليزية ولا
/// تصلح للعرض — تُستبدل برسالة عامة بدل تسريب تفاصيل داخلية إلى الشاشة.
/// EN: Maps any thrown object to a user-facing message, hiding internal errors.
String friendlyErrorMessage(Object error) {
  if (error is ApiException) return error.message;

  return 'حدث خطأ غير متوقع. حاول مرة أخرى.';
}
