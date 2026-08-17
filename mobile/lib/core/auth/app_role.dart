/// أدوار الموبايل كما يعيدها الخادم (مطابقة لـ `MobileAppRole` في Laravel).
///
/// Arabic: تُستخدم للتوجيه بعد تسجيل الدخول وفي `labelAr` لعرض التسمية العربية.
/// EN: Backend role strings for navigation and localized role labels.
abstract final class AppRole {
  static const String teacher = 'Teacher';
  static const String examiner = 'Examiner';
  static const String centerSupervisor = 'CenterSupervisor';

  static String? labelAr(String? role) {
    switch (role) {
      case teacher:
        return 'معلّم';
      case examiner:
        return 'مختبر';
      case centerSupervisor:
        return 'مشرف حلقات';
      default:
        return null;
    }
  }
}
