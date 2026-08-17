import 'package:flutter_test/flutter_test.dart';
import 'package:halqati_mobile/core/offline/offline_gateway.dart';
import 'package:halqati_mobile/core/offline/sync_manager.dart';

/// اختبارات سياسة المزامنة الخالصة (بلا قاعدة بيانات ولا شبكة).
///
/// Arabic: تركّز على القرار الأخطر في الطبقة: أي خطأ يستحق إعادة المحاولة وأيها لا.
/// خطأ صُنّف مؤقتاً وهو نهائي يعني طابوراً يعيد المحاولة إلى الأبد؛ وخطأ صُنّف نهائياً
/// وهو مؤقت يعني إسقاط عمل المعلّم لمجرد ضعف الشبكة.
/// EN: Pure policy tests for retry classification, backoff, and timestamp format.
void main() {
  group('تصنيف الأخطاء', () {
    test('انقطاع الشبكة (بلا رمز حالة) يُعتبر مؤقتاً', () {
      expect(SyncManager.isPermanentStatus(null), isFalse);
    });

    test('أخطاء التحقق والصلاحية نهائية', () {
      expect(SyncManager.isPermanentStatus(422), isTrue);
      expect(SyncManager.isPermanentStatus(403), isTrue);
      expect(SyncManager.isPermanentStatus(404), isTrue);
    });

    test('انتهاء الجلسة مؤقت حتى لا تضيع البيانات بإعادة تسجيل الدخول', () {
      expect(SyncManager.isPermanentStatus(401), isFalse);
    });

    test('المهلة وتجاوز حد الطلبات مؤقتان', () {
      expect(SyncManager.isPermanentStatus(408), isFalse);
      expect(SyncManager.isPermanentStatus(429), isFalse);
    });

    test('أخطاء الخادم مؤقتة', () {
      expect(SyncManager.isPermanentStatus(500), isFalse);
      expect(SyncManager.isPermanentStatus(503), isFalse);
    });
  });

  group('التباعد بين المحاولات', () {
    test('يتزايد مع كل محاولة', () {
      final first = SyncManager.backoffFor(1);
      final second = SyncManager.backoffFor(2);
      final third = SyncManager.backoffFor(3);

      expect(second, greaterThan(first));
      expect(third, greaterThan(second));
    });

    test('لا يتجاوز نصف ساعة مهما بلغ عدد المحاولات', () {
      for (final attempts in [8, 12, 40, 500]) {
        expect(
          SyncManager.backoffFor(attempts),
          lessThanOrEqualTo(const Duration(minutes: 30)),
          reason: 'المحاولة رقم $attempts تجاوزت السقف',
        );
      }
    });
  });

  group('صياغة الطابع الزمني', () {
    test('تطابق الصيغة التي يقبلها تحقّق Laravel من التواريخ', () {
      final formatted = OfflineGateway.formatTimestamp(DateTime(2026, 8, 9, 8, 5, 3));

      expect(formatted, '2026-08-09 08:05:03');
    });

    test('تُصفّر الخانات لأرقام من رقمين', () {
      final formatted = OfflineGateway.formatTimestamp(DateTime(2026, 1, 2, 3, 4, 5));

      expect(formatted, '2026-01-02 03:04:05');
    });
  });
}
