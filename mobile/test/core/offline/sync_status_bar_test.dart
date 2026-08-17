import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:halqati_mobile/core/offline/offline_models.dart';
import 'package:halqati_mobile/core/offline/widgets/sync_status_bar.dart';

/// اختبارات شريط حالة المزامنة.
///
/// Arabic: القاعدة الأساسية أنه يختفي تماماً في الحالة الطبيعية — شريط دائم الظهور
/// يصير خلفيةً بصرية يتجاهلها المستخدم، فيفقد قيمته حين يهمّ فعلاً.
/// EN: The bar must vanish when there is nothing to report.
void main() {
  Future<void> pumpBar(WidgetTester tester, SyncSnapshot snapshot) {
    return tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: SyncStatusBar(
            listenable: ValueNotifier<SyncSnapshot>(snapshot),
          ),
        ),
      ),
    );
  }

  testWidgets('يختفي عند الاتصال وخلوّ الطابور', (tester) async {
    await pumpBar(tester, const SyncSnapshot(isOnline: true));

    expect(find.byType(Material), findsWidgets);
    expect(find.textContaining('دون إنترنت'), findsNothing);
    expect(find.byIcon(Icons.cloud_off), findsNothing);
  });

  testWidgets('يعرض وضع دون إنترنت مع طمأنة بإمكان المتابعة', (tester) async {
    await pumpBar(tester, const SyncSnapshot(isOnline: false));

    expect(find.byIcon(Icons.cloud_off), findsOneWidget);
    expect(find.textContaining('يمكنك متابعة العمل'), findsOneWidget);
  });

  testWidgets('يذكر عدد العمليات المحفوظة عند انقطاع الشبكة', (tester) async {
    await pumpBar(tester, const SyncSnapshot(isOnline: false, pendingCount: 3));

    expect(find.textContaining('3 عملية محفوظة'), findsOneWidget);
  });

  testWidgets('الفشل النهائي يطغى على بقية الحالات', (tester) async {
    await pumpBar(
      tester,
      const SyncSnapshot(isOnline: true, pendingCount: 5, failedCount: 2),
    );

    expect(find.byIcon(Icons.error_outline), findsOneWidget);
    expect(find.textContaining('2 عملية لم تُقبل'), findsOneWidget);
  });

  testWidgets('يعرض مؤشر تقدم أثناء المزامنة', (tester) async {
    await pumpBar(
      tester,
      const SyncSnapshot(isOnline: true, isSyncing: true, pendingCount: 1),
    );

    expect(find.byType(CircularProgressIndicator), findsOneWidget);
    expect(find.textContaining('جارٍ المزامنة'), findsOneWidget);
  });

  testWidgets('لا ينهار حين لا تكون طبقة المزامنة مهيّأة', (tester) async {
    await tester.pumpWidget(
      const MaterialApp(home: Scaffold(body: SyncStatusBar())),
    );

    expect(tester.takeException(), isNull);
  });
}
