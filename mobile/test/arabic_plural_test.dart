import 'package:flutter_test/flutter_test.dart';
import 'package:halqati_mobile/core/utils/arabic_plural.dart';

void main() {
  group('مطابقة العدد للمعدود', () {
    test('الصفر', () => expect(ArCount.students(0), 'لا طلاب'));
    test('المفرد', () => expect(ArCount.students(1), 'طالب واحد'));
    test('المثنى', () => expect(ArCount.students(2), 'طالبان'));

    test('جمع القلّة من ٣ إلى ١٠', () {
      expect(ArCount.students(3), '3 طلاب');
      expect(ArCount.students(10), '10 طلاب');
    });

    test('المفرد المنصوب من ١١ فأكثر', () {
      expect(ArCount.students(11), '11 طالباً');
      expect(ArCount.students(43), '43 طالباً');
      expect(ArCount.students(190), '190 طالباً');
    });

    test('المئات الصحيحة تأخذ المفرد', () {
      expect(ArCount.students(100), '100 طالب');
      expect(ArCount.students(1000), '1000 طالب');
    });

    test('العدد المركّب يتبع خانتيه الأخيرتين', () {
      expect(ArCount.students(105), '105 طلاب');
      expect(ArCount.students(115), '115 طالباً');
    });

    test('حلقات', () {
      expect(ArCount.halaqahs(1), 'حلقة واحدة');
      expect(ArCount.halaqahs(5), '5 حلقات');
      expect(ArCount.halaqahs(30), '30 حلقة');
    });

    test('عمليات المزامنة', () {
      expect(ArCount.operations(1), 'عملية واحدة');
      expect(ArCount.operations(4), '4 عمليات');
      expect(ArCount.operations(12), '12 عملية');
    });

    test('أرصاد الحضور', () {
      expect(ArCount.records(2), 'رصدان');
      expect(ArCount.records(7), '7 أرصاد');
      expect(ArCount.records(50), '50 رصداً');
    });
  });
}
