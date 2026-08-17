/// موديلات قسم المختبر (Examiner) في تطبيق الموبايل.
///
/// Arabic: تحتوي DTOs لتحويل استجابات API إلى كائنات Dart قابلة للاستخدام في الواجهات.
/// EN: DTO models for examiner-related API responses and requests.
library;

import 'package:flutter/material.dart';

/// إحصاءات لوحة المختبر (تعيينات، مكتمل، اليوم).
/// EN: Examiner dashboard stats from `/examiner/stats`.
class ExaminerStats {
  final int totalAssignments;
  final int completed;
  final int pending;
  final int todayExamined;
  final double? todayAvgScore;
  final int? todayHighestScore;
  final int? todayLowestScore;

  ExaminerStats({
    required this.totalAssignments,
    required this.completed,
    required this.pending,
    required this.todayExamined,
    this.todayAvgScore,
    this.todayHighestScore,
    this.todayLowestScore,
  });

  factory ExaminerStats.fromJson(Map<String, dynamic> j) {
    return ExaminerStats(
      totalAssignments: j['total_assignments'] as int? ?? 0,
      completed: j['completed'] as int? ?? 0,
      pending: j['pending'] as int? ?? 0,
      todayExamined: j['today_examined'] as int? ?? 0,
      todayAvgScore: (j['today_avg_score'] as num?)?.toDouble(),
      todayHighestScore: j['today_highest_score'] as int?,
      todayLowestScore: j['today_lowest_score'] as int?,
    );
  }
}

/// تفاصيل طالب للمختبر (الملف، آخر النتائج، الحلقة).
/// EN: Student detail payload for examiner flows.
class StudentDetail {
  final int id;
  final String name;
  final String? photoUrl;
  final String? halaqahName;
  final String? centerName;
  final int memorizedParts;
  final String? currentSurah;
  final List<PastResult> lastResults;
  final int resultsCount;

  StudentDetail({
    required this.id,
    required this.name,
    this.photoUrl,
    this.halaqahName,
    this.centerName,
    required this.memorizedParts,
    this.currentSurah,
    required this.lastResults,
    required this.resultsCount,
  });

  factory StudentDetail.fromJson(Map<String, dynamic> j) {
    final raw = j['last_results'];
    final list = raw is List
        ? raw.map((e) => PastResult.fromJson(Map<String, dynamic>.from(e as Map))).toList()
        : <PastResult>[];
    return StudentDetail(
      id: j['id'] as int,
      name: j['name'] as String,
      photoUrl: j['photo_url'] as String?,
      halaqahName: j['halaqah_name'] as String?,
      centerName: j['center_name'] as String?,
      memorizedParts: j['memorized_parts'] as int? ?? 0,
      currentSurah: j['current_surah'] as String?,
      lastResults: list,
      resultsCount: j['results_count'] as int? ?? 0,
    );
  }
}

/// نتيجة اختبار سابقة في سجل الطالب.
/// EN: A single past test result row.
class PastResult {
  final String date;
  final int totalScore;
  final int memorizationScore;
  final int tajweedScore;
  final int reviewScore;
  final String? testedSurah;
  final String? notes;
  final String? levelAr;

  PastResult({
    required this.date,
    required this.totalScore,
    required this.memorizationScore,
    required this.tajweedScore,
    required this.reviewScore,
    this.testedSurah,
    this.notes,
    this.levelAr,
  });

  factory PastResult.fromJson(Map<String, dynamic> j) {
    return PastResult(
      date: j['date']?.toString() ?? '',
      totalScore: (j['total_score'] as num?)?.round() ?? 0,
      memorizationScore: (j['memorization_score'] as num?)?.round() ?? 0,
      tajweedScore: (j['tajweed_score'] as num?)?.round() ?? 0,
      reviewScore: (j['review_score'] as num?)?.round() ?? 0,
      testedSurah: j['tested_surah'] as String?,
      notes: j['notes'] as String?,
      levelAr: j['level_ar'] as String?,
    );
  }
}

/// جسم POST لحفظ/تحديث نتيجة اختبار (`/examiner/test-results`).
/// EN: Request body for submitting examiner scores.
class TestResultRequest {
  final int studentId;
  final int testId;
  final String testedSurah;
  final int memorizationScore;
  final int tajweedScore;
  final int reviewScore;
  final String? notes;

  TestResultRequest({
    required this.studentId,
    required this.testId,
    required this.testedSurah,
    required this.memorizationScore,
    required this.tajweedScore,
    required this.reviewScore,
    this.notes,
  });

  Map<String, dynamic> toJson() => {
        'student_id': studentId,
        'test_id': testId,
        'tested_surah': testedSurah,
        'memorization_score': memorizationScore,
        'tajweed_score': tajweedScore,
        'review_score': reviewScore,
        if (notes != null && notes!.trim().isNotEmpty) 'notes': notes,
      };
}

/// استجابة الخادم بعد حفظ النتيجة (المجموع والمستوى).
/// EN: Server acknowledgment after storing a test result.
class TestResultResponse {
  final String message;
  final int totalScore;
  final String level;
  final bool isUpdate;

  TestResultResponse({
    required this.message,
    required this.totalScore,
    required this.level,
    required this.isUpdate,
  });

  factory TestResultResponse.fromJson(Map<String, dynamic> j) {
    return TestResultResponse(
      message: j['message']?.toString() ?? '',
      totalScore: (j['total_score'] as num).round(),
      level: j['level']?.toString() ?? '',
      isUpdate: j['is_update'] as bool? ?? false,
    );
  }
}

/// ملخص يوم للمختبر (توزيع الدرجات، قائمة النتائج).
/// EN: Daily summary payload for examiner daily-summary screen.
class DailySummary {
  final String date;
  final int totalExamined;
  final double avgScore;
  final int highestScore;
  final int lowestScore;
  final Map<String, int> distribution;
  final List<Map<String, dynamic>> results;

  DailySummary({
    required this.date,
    required this.totalExamined,
    required this.avgScore,
    required this.highestScore,
    required this.lowestScore,
    required this.distribution,
    required this.results,
  });

  factory DailySummary.fromJson(Map<String, dynamic> j) {
    final dist = j['distribution'];
    return DailySummary(
      date: j['date']?.toString() ?? '',
      totalExamined: j['total_examined'] as int? ?? 0,
      avgScore: (j['avg_score'] as num?)?.toDouble() ?? 0,
      highestScore: (j['highest_score'] as num?)?.round() ?? 0,
      lowestScore: (j['lowest_score'] as num?)?.round() ?? 0,
      distribution: dist is Map
          ? dist.map((k, v) => MapEntry(k.toString(), (v as num).round()))
          : <String, int>{},
      results: (j['results'] as List<dynamic>?)
              ?.map((e) => Map<String, dynamic>.from(e as Map))
              .toList() ??
          [],
    );
  }
}

/// تحويل المجموع إلى تسمية عربية للمستوى.
/// EN: Maps total score to a human-readable Arabic level label.
String levelLabelFromTotal(int total) {
  if (total >= 90) return 'ممتاز';
  if (total >= 75) return 'جيد جداً';
  if (total >= 60) return 'جيد';
  if (total >= 50) return 'مقبول';
  return 'ضعيف';
}

/// لون مميز للمستوى بناءً على المجموع.
/// EN: Returns an accent color for a given score level.
Color levelAccentColor(int total) {
  if (total >= 90) return const Color(0xFF059669);
  if (total >= 75) return const Color(0xFF0D9488);
  if (total >= 60) return const Color(0xFF2563EB);
  if (total >= 50) return const Color(0xFFD97706);
  return const Color(0xFFDC2626);
}
