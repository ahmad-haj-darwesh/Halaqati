import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/annotations.dart';
import 'package:mockito/mockito.dart';
import 'package:dio/dio.dart';

import 'package:halqati_mobile/features/home/data/teacher_daily_repository.dart';
import 'package:halqati_mobile/services/api/api_client.dart';

import 'teacher_daily_repository_test.mocks.dart';

@GenerateMocks([ApiClient])
void main() {
  group('TeacherDailyRepositoryImpl', () {
    late MockApiClient mockApiClient;
    late TeacherDailyRepositoryImpl repo;

    setUp(() {
      mockApiClient = MockApiClient();
      repo = TeacherDailyRepositoryImpl(apiClient: mockApiClient);
    });

    test('getToday parses students and reasons', () async {
      when(mockApiClient.get(any)).thenAnswer(
        (_) async => Response(
          requestOptions: RequestOptions(path: '/teacher/halaqah/today'),
          data: {
            'date': '2026-04-01',
            'halaqah_id': 1,
            'students': [
              {
                'id': 10,
                'full_name': 'Student A',
                'date': '2026-04-01',
                'attendance': {'status': 'present', 'notes': 'ok'},
                'evaluation': {
                  'overall': 'excellent',
                  'general_note': 'great',
                  'reason_ids': [1]
                },
                'memorization': {
                  'memorization_from': 'A',
                  'memorization_to': 'B',
                  'revision_from': null,
                  'revision_to': null,
                  'mistakes': 'none'
                }
              }
            ],
            'reasons': [
              {'id': 1, 'key': 'ex_homework', 'label': 'واجب', 'type': 'excellence'}
            ]
          },
        ),
      );

      final res = await repo.getToday(date: '2026-04-01');

      expect(res.date, '2026-04-01');
      expect(res.students, hasLength(1));
      expect(res.students.first.fullName, 'Student A');
      expect(res.reasons.first.label, 'واجب');
    });

    test('upsertDailyRecords sends correct payload', () async {
      when(mockApiClient.post(any, data: anyNamed('data'))).thenAnswer(
        (_) async => Response(
          requestOptions: RequestOptions(path: '/teacher/daily-records/upsert'),
          data: {'message': 'saved'},
        ),
      );

      final students = [
        StudentTodayDto(id: 1, fullName: 'A', date: '2026-04-01')
          ..attendanceStatus = 'present'
          ..evaluationOverall = 'good'
          ..reasonIds = [2]
          ..mistakes = 'm',
      ];

      await repo.upsertDailyRecords(date: '2026-04-01', students: students);

      final captured = verify(
        mockApiClient.post(any, data: captureAnyNamed('data')),
      ).captured.single as Map<String, dynamic>;

      expect(captured['date'], '2026-04-01');
      expect((captured['records'] as List).length, 1);
      expect((captured['records'] as List).first['student_id'], 1);
      expect((captured['records'] as List).first['attendance_status'], 'present');
      expect((captured['records'] as List).first['evaluation_overall'], 'good');
      expect((captured['records'] as List).first['reason_ids'], [2]);
      expect((captured['records'] as List).first['mistakes'], 'm');
    });
  });
}

