import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:halqati_mobile/features/home/data/teacher_daily_repository.dart';
import 'package:halqati_mobile/features/home/presentation/daily_record_page.dart';

class FakeTeacherDailyRepository implements TeacherDailyRepository {
  int upsertCalls = 0;
  List<StudentTodayDto>? lastStudents;
  String? lastDate;

  @override
  Future<HalaqahTodayResponseDto> getToday({String? date}) async {
    final d = date ?? '2026-04-01';
    return HalaqahTodayResponseDto(
      date: d,
      halaqahId: 1,
      students: [
        StudentTodayDto(id: 1, fullName: 'Student A', date: d),
        StudentTodayDto(id: 2, fullName: 'Student B', date: d),
      ],
      reasons: const [
        EvaluationReasonDto(id: 1, key: 'ex_homework', label: 'واجب', type: 'excellence'),
      ],
    );
  }

  @override
  Future<MonthlyReportDto> getMonthlyReport({required String month}) async {
    throw UnimplementedError();
  }

  @override
  Future<void> upsertDailyRecords({required String date, required List<StudentTodayDto> students}) async {
    upsertCalls += 1;
    lastDate = date;
    lastStudents = students;
  }
}

void main() {
  testWidgets('loads today endpoint and shows students list', (tester) async {
    final repo = FakeTeacherDailyRepository();

    await tester.pumpWidget(
      MaterialApp(home: DailyRecordPage(repository: repo)),
    );

    await tester.pumpAndSettle();

    expect(find.text('Student A'), findsOneWidget);
    expect(find.text('Student B'), findsOneWidget);
  });

  testWidgets('selecting chip then save triggers upsert call', (tester) async {
    final repo = FakeTeacherDailyRepository();

    await tester.pumpWidget(
      MaterialApp(home: DailyRecordPage(repository: repo)),
    );

    await tester.pumpAndSettle();

    // Select "حاضر" for first student
    await tester.tap(find.text('حاضر').first);
    await tester.pump();

    await tester.tap(find.byKey(const Key('save_all_button')));
    await tester.pumpAndSettle();

    expect(repo.upsertCalls, 1);
    expect(repo.lastStudents, isNotNull);
    expect(repo.lastStudents!.first.attendanceStatus, 'present');
  });
}

