import 'package:dio/dio.dart';

import '../../../core/constants/api_constants.dart';
import '../../../services/api/api_client.dart';

/// DTOs ومستودع المعلم للسجلات اليومية وتقرير الشهر.
///
/// Arabic: يطابق `/teacher/halaqah/today`، `upsert`، و`reports/monthly`.
/// EN: Teacher daily/monthly API models and `TeacherDailyRepository` implementation.

// --- DTOs ---

/// سبب تقييم (تمييز/تقصير) كما يعيده الخادم.
class EvaluationReasonDto {
  final int id;
  final String key;
  final String label;
  final String type; // excellence|deficiency

  const EvaluationReasonDto({
    required this.id,
    required this.key,
    required this.label,
    required this.type,
  });

  factory EvaluationReasonDto.fromJson(Map<String, dynamic> json) {
    return EvaluationReasonDto(
      id: json['id'] as int,
      key: json['key'] as String,
      label: json['label'] as String,
      type: json['type'] as String,
    );
  }
}

/// حالة طالب ليوم واحد.
class StudentTodayDto {
  final int id;
  final String fullName;
  final String date;

  String? attendanceStatus;
  String? attendanceNote;

  String? evaluationOverall;
  String? generalNote;
  List<int> reasonIds;

  String? memorizationFrom;
  String? memorizationTo;
  String? revisionFrom;
  String? revisionTo;
  String? mistakes;

  bool canEditEvaluation;

  StudentTodayDto({
    required this.id,
    required this.fullName,
    required this.date,
    this.attendanceStatus,
    this.attendanceNote,
    this.evaluationOverall,
    this.generalNote,
    List<int>? reasonIds,
    this.memorizationFrom,
    this.memorizationTo,
    this.revisionFrom,
    this.revisionTo,
    this.mistakes,
    this.canEditEvaluation = true,
  }) : reasonIds = reasonIds ?? [];

  factory StudentTodayDto.fromJson(Map<String, dynamic> json) {
    final attendance = json['attendance'] as Map<String, dynamic>?;
    final evaluation = json['evaluation'] as Map<String, dynamic>?;
    final memorization = json['memorization'] as Map<String, dynamic>?;

    final bool canEditEval = evaluation == null ? true : (evaluation['can_edit'] as bool?) ?? true;

    return StudentTodayDto(
      id: json['id'] as int,
      fullName: json['full_name'] as String,
      date: json['date'] as String,
      attendanceStatus: attendance?['status'] as String?,
      attendanceNote: attendance?['notes'] as String?,
      evaluationOverall: evaluation?['overall'] as String?,
      generalNote: evaluation?['general_note'] as String?,
      reasonIds: (evaluation?['reason_ids'] as List<dynamic>?)?.map((e) => e as int).toList() ?? const [],
      memorizationFrom: memorization?['memorization_from'] as String?,
      memorizationTo: memorization?['memorization_to'] as String?,
      revisionFrom: memorization?['revision_from'] as String?,
      revisionTo: memorization?['revision_to'] as String?,
      mistakes: memorization?['mistakes'] as String?,
      canEditEvaluation: canEditEval,
    );
  }

  Map<String, dynamic> toUpsertRecordJson() {
    return {
      'student_id': id,
      'attendance_status': attendanceStatus,
      'attendance_note': attendanceNote,
      'evaluation_overall': evaluationOverall,
      'reason_ids': reasonIds,
      'general_note': generalNote,
      'memorization_from': memorizationFrom,
      'memorization_to': memorizationTo,
      'revision_from': revisionFrom,
      'revision_to': revisionTo,
      'mistakes': mistakes,
    };
  }
}

class HalaqahTodayResponseDto {
  final String date;
  final int? halaqahId;
  final List<StudentTodayDto> students;
  final List<EvaluationReasonDto> reasons;

  const HalaqahTodayResponseDto({
    required this.date,
    required this.halaqahId,
    required this.students,
    required this.reasons,
  });

  factory HalaqahTodayResponseDto.fromJson(Map<String, dynamic> json) {
    return HalaqahTodayResponseDto(
      date: json['date'] as String,
      halaqahId: json['halaqah_id'] as int?,
      students: (json['students'] as List<dynamic>).map((e) => StudentTodayDto.fromJson(e as Map<String, dynamic>)).toList(),
      reasons: (json['reasons'] as List<dynamic>).map((e) => EvaluationReasonDto.fromJson(e as Map<String, dynamic>)).toList(),
    );
  }
}

class MonthlyReportDto {
  final String month;
  final Map<String, int> attendance;
  final Map<String, int> evaluations;
  final List<Map<String, dynamic>> reasons;

  const MonthlyReportDto({
    required this.month,
    required this.attendance,
    required this.evaluations,
    required this.reasons,
  });

  factory MonthlyReportDto.fromJson(Map<String, dynamic> json) {
    Map<String, int> toIntMap(dynamic v) {
      if (v is Map) {
        return v.map((k, val) => MapEntry(k.toString(), (val as num).toInt()));
      }
      return {};
    }

    return MonthlyReportDto(
      month: json['month'] as String,
      attendance: toIntMap(json['attendance']),
      evaluations: toIntMap(json['evaluations']),
      reasons: (json['reasons'] as List<dynamic>).map((e) => e as Map<String, dynamic>).toList(),
    );
  }
}

// --- Repository Architecture ---

/// واجهة المستودع لضمان سهولة الاختبار وفصل التنفيذ
abstract class TeacherDailyRepository {
  Future<HalaqahTodayResponseDto> getToday({String? date});
  Future<void> upsertDailyRecords({required String date, required List<StudentTodayDto> students});
  Future<MonthlyReportDto> getMonthlyReport({required String month});
}

/// تنفيذ المستودع (Implementation)
class TeacherDailyRepositoryImpl implements TeacherDailyRepository {
  final ApiClient _apiClient;

  const TeacherDailyRepositoryImpl({required ApiClient apiClient}) : _apiClient = apiClient;

  @override
  Future<HalaqahTodayResponseDto> getToday({String? date}) async {
    final endpoint = date == null ? ApiConstants.teacherTodayEndpoint : '${ApiConstants.teacherTodayEndpoint}?date=$date';
    final Response res = await _apiClient.get(endpoint);
    return HalaqahTodayResponseDto.fromJson(res.data as Map<String, dynamic>);
  }

  @override
  Future<void> upsertDailyRecords({required String date, required List<StudentTodayDto> students}) async {
    await _apiClient.post(
      ApiConstants.teacherUpsertDailyEndpoint,
      data: {
        'date': date,
        'records': students.map((s) => s.toUpsertRecordJson()).toList(),
      },
    );
  }

  @override
  Future<MonthlyReportDto> getMonthlyReport({required String month}) async {
    final Response res = await _apiClient.get('${ApiConstants.teacherMonthlyReportEndpoint}?month=$month');
    return MonthlyReportDto.fromJson(res.data as Map<String, dynamic>);
  }
}