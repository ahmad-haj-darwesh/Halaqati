import 'package:flutter_test/flutter_test.dart';
import 'package:halqati_mobile/services/api/response_cache.dart';

/// اختبارات كاش استجابات القراءة.
///
/// Arabic: تغطي السلوك الذي طُلب — الصفحة لا يُعاد تحميلها عند العودة إليها —
/// وحدود الكاش: انتهاء العمر، الإبطال بعد الكتابة، والمسح عند تبديل المستخدم.
void main() {
  setUp(() {
    ResponseCache.clear();
    ResponseCache.defaultTtl = const Duration(minutes: 5);
  });

  test('يعيد القيمة المخزّنة بدل إعادة الطلب', () {
    ResponseCache.write('/supervisor/attendance-stats', {'days': 7}, {'rate': 91});

    expect(
      ResponseCache.read('/supervisor/attendance-stats', {'days': 7}),
      {'rate': 91},
    );
  });

  test('اختلاف معاملات الاستعلام يعني مدخلاً مختلفاً', () {
    ResponseCache.write('/supervisor/attendance-stats', {'days': 7}, {'rate': 91});

    expect(ResponseCache.read('/supervisor/attendance-stats', {'days': 30}), isNull);
  });

  test('ترتيب معاملات الاستعلام لا يغيّر المفتاح', () {
    expect(
      ResponseCache.keyFor('/x', {'b': 2, 'a': 1}),
      ResponseCache.keyFor('/x', {'a': 1, 'b': 2}),
    );
  });

  test('المدخل ينتهي بانتهاء عمره', () async {
    ResponseCache.defaultTtl = const Duration(milliseconds: 40);
    ResponseCache.write('/supervisor/stats', null, {'x': 1});

    expect(ResponseCache.read('/supervisor/stats', null), isNotNull);

    await Future<void>.delayed(const Duration(milliseconds: 80));

    expect(ResponseCache.read('/supervisor/stats', null), isNull);
  });

  test('الإبطال بالبادئة يمسّ القسم المقصود وحده', () {
    ResponseCache.write('/supervisor/stats', null, {'a': 1});
    ResponseCache.write('/teacher/daily', null, {'b': 2});

    ResponseCache.invalidatePrefix('/supervisor');

    expect(ResponseCache.read('/supervisor/stats', null), isNull);
    expect(ResponseCache.read('/teacher/daily', null), isNotNull);
  });

  test('المسح الكامل يفرّغ الكاش — تبديل المستخدم', () {
    ResponseCache.write('/supervisor/stats', null, {'a': 1});
    ResponseCache.write('/teacher/daily', null, {'b': 2});

    ResponseCache.clear();

    expect(ResponseCache.size, 0);
  });

  test('الأعمار المخصّصة تُطبّق حسب بداية المسار', () async {
    ResponseCache.defaultTtl = const Duration(milliseconds: 40);
    ResponseCache.ttlOverrides['/supervisor/centers'] = const Duration(minutes: 30);

    ResponseCache.write('/supervisor/centers', null, {'a': 1});
    ResponseCache.write('/supervisor/stats', null, {'b': 2});

    await Future<void>.delayed(const Duration(milliseconds: 80));

    // الطويل باقٍ، القصير انتهى
    expect(ResponseCache.read('/supervisor/centers', null), isNotNull);
    expect(ResponseCache.read('/supervisor/stats', null), isNull);
  });

  test('القيمة الفارغة لا تُخزَّن', () {
    ResponseCache.write('/x', null, null);

    expect(ResponseCache.size, 0);
  });
}
