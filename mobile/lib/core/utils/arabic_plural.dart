/// تصريف المعدود في العربية.
///
/// Arabic: العربية تغيّر صيغة المعدود حسب العدد — «طالب واحد»، «طالبان»،
/// «٥ طلاب»، «١٥ طالباً». دمج الرقم بنص ثابت (`'$n طالب'`) ينتج «١ حلقات»
/// و«٥ عملية» وهي أخطاء ظاهرة للمستخدم. هذه الدالة تختار الصيغة الصحيحة.
///
/// EN: Arabic counted-noun agreement (one / dual / 3–10 plural / 11+ singular accusative).
library;

String arabicCount(
  int count, {
  /// صيغة المفرد: «طالب»
  required String one,

  /// صيغة المثنى: «طالبان»
  required String two,

  /// جمع القلّة (٣–١٠): «طلاب»
  required String few,

  /// المفرد المنصوب (١١ فأكثر): «طالباً»
  required String many,

  /// نص بديل عند الصفر، وإلا يُستخدم «لا <جمع>»
  String? zero,

  /// المعدود مؤنث — «حلقة واحدة» لا «حلقة واحد».
  bool feminine = false,
}) {
  if (count == 0) return zero ?? 'لا $few';
  if (count == 1) return '$one ${feminine ? 'واحدة' : 'واحد'}';
  if (count == 2) return two;

  // المئات والآلاف الصحيحة تأخذ المفرد: «١٠٠ طالب»
  if (count % 100 == 0) return '$count $one';

  final lastTwo = count % 100;
  if (lastTwo >= 3 && lastTwo <= 10) return '$count $few';

  return '$count $many';
}

/// اختصارات للمعدودات المتكرّرة في التطبيق.
class ArCount {
  const ArCount._();

  static String students(int n) => arabicCount(
        n,
        one: 'طالب',
        two: 'طالبان',
        few: 'طلاب',
        many: 'طالباً',
        zero: 'لا طلاب',
      );

  static String halaqahs(int n) => arabicCount(
        n,
        one: 'حلقة',
        two: 'حلقتان',
        few: 'حلقات',
        many: 'حلقة',
        zero: 'لا حلقات',
        feminine: true,
      );

  static String parts(int n) => arabicCount(
        n,
        one: 'جزء',
        two: 'جزآن',
        few: 'أجزاء',
        many: 'جزءاً',
        zero: 'لا شيء',
      );

  static String operations(int n) => arabicCount(
        n,
        one: 'عملية',
        two: 'عمليتان',
        few: 'عمليات',
        many: 'عملية',
        zero: 'لا عمليات',
        feminine: true,
      );

  static String records(int n) => arabicCount(
        n,
        one: 'رصد',
        two: 'رصدان',
        few: 'أرصاد',
        many: 'رصداً',
        zero: 'لا أرصاد',
      );

  static String days(int n) => arabicCount(
        n,
        one: 'يوم',
        two: 'يومان',
        few: 'أيام',
        many: 'يوماً',
      );
}
