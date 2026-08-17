/// ثوابت الاتصال بـ Laravel: `baseUrl`، مسارات REST، والمهل الزمنية.
///
/// Arabic: تم إعداد هذا الملف للإنتاج (Production) للاتصال بالسيرفر الحقيقي.
/// EN: API base URL, endpoint paths, timeouts, and public media URL resolution.
class ApiConstants {
  ApiConstants._();

  // 👈 تم وضع رابط السيرفر الحقيقي هنا للإنتاج
  static const String baseUrl = 'https://halaqati.wuaze.com/api';

  // مسارات المصادقة والإشعارات
  static const String loginEndpoint = '/login';
  static const String logoutEndpoint = '/logout';
  static const String sessionEndpoint = '/session';
  static const String fcmTokenEndpoint = '/fcm-token';
  
  static const String notificationsEndpoint = '/notifications';
  static const String notificationsUnreadCountEndpoint = '/notifications/unread-count';
  static const String notificationsReadAllEndpoint = '/notifications/read-all';
  static String notificationReadEndpoint(int id) => '/notifications/$id/read';
  
  // مسارات المعلم
  static const String teacherMeEndpoint = '/teacher/me';
  static const String teacherProfileEndpoint = '/teacher/profile';
  static const String teacherStudentsEndpoint = '/teacher/students';
  static String teacherStudentProfile(int studentId) => '/teacher/students/$studentId/profile';
  static String teacherStudentProfileSubmit(int studentId) => '/teacher/students/$studentId/profile/submit';
  static const String teacherTodayEndpoint = '/teacher/halaqah/today';
  static const String teacherUpsertDailyEndpoint = '/teacher/daily-records/upsert';
  static const String teacherMonthlyReportEndpoint = '/teacher/reports/monthly';

  // مسارات المختبر
  static const String examinerMeEndpoint = '/examiner/me';
  static const String examinerTestsEndpoint = '/examiner/tests';
  static const String examinerTestAssignmentsEndpoint = '/examiner/test-assignments';
  static const String examinerTestResultsEndpoint = '/examiner/test-results';
  static const String examinerStats = '/examiner/stats';
  static const String examinerStudentDetailPrefix = '/examiner/students/';
  static const String examinerStoreResult = '/examiner/test-results';
  static const String examinerDailySummary = '/examiner/daily-summary';
  static String examinerAssignmentsForTest(int testId) => '/examiner/tests/$testId/assignments';
  
  // مسارات المشرف
  static const String supervisorMeEndpoint = '/supervisor/me';
  static const String supervisorCentersEndpoint = '/supervisor/centers';
  static const String supervisorHalaqahsEndpoint = '/supervisor/halaqahs';
  static const String supervisorTeachersEndpoint = '/supervisor/teachers';
  static const String supervisorStats = '/supervisor/stats';
  static const String supervisorTeacherDetail = '/supervisor/teachers/';
  static const String supervisorHalaqahDaily = '/supervisor/halaqahs/';
  static const String supervisorStoreVisit = '/supervisor/visits';
  static const String supervisorMyVisits = '/supervisor/visits';
  static const String supervisorAttendance = '/supervisor/attendance-stats';

  // مهلة الاتصال
  static const Duration connectTimeout = Duration(seconds: 30); // 👈 زدنا المهلة لـ 30 ثانية لتناسب الإنترنت عبر الهاتف
  static const Duration receiveTimeout = Duration(seconds: 30);

  /// يحوّل مسار التخزين النسبي (`/storage/...`) إلى رابط مطلق على نفس مضيف الـ API.
  static String resolvePublicMediaUrl(String? url) {
    if (url == null || url.trim().isEmpty) {
      return '';
    }
    final t = url.trim();
    if (t.startsWith('http://') || t.startsWith('https://')) {
      return t;
    }
    final path = t.startsWith('/') ? t : '/$t';
    final origin = Uri.parse(baseUrl).origin; // سيأخذ https://halaqati.wuaze.com
    return '$origin$path';
  }
}