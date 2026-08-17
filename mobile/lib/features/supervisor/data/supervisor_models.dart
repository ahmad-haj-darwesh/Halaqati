/// نماذج JSON لمسارات المشرف (Supervisor API).
///
/// Arabic: DTOs تطابق استجابات `/api/supervisor/*` (إحصاءات، زيارات، حضور، إلخ).
/// EN: Data transfer objects for supervisor mobile features.
library;

/// إحصاءات لوحة المشرف (زيارات، متوسطات، حضور، مراكز).
/// EN: Dashboard KPIs returned by supervisor stats endpoint.
class SupervisorStats {
  final int visitsThisMonth;
  final double avgTeachingScore;
  final double avgPlanScore;
  final double avgEngagement;
  final int unvisitedHalaqahs;
  final double? attendanceRate7d;
  final int centersCount;

  SupervisorStats.fromJson(Map<String, dynamic> j)
      : visitsThisMonth = j['visits_this_month'] as int,
        avgTeachingScore = (j['avg_teaching_score'] as num).toDouble(),
        avgPlanScore = (j['avg_plan_score'] as num).toDouble(),
        avgEngagement = (j['avg_engagement'] as num).toDouble(),
        unvisitedHalaqahs = j['unvisited_halaqahs'] as int,
        attendanceRate7d = (j['attendance_rate_7d'] as num?)?.toDouble(),
        centersCount = j['centers_count'] as int;
}

/// تفاصيل معلّم لشاشة المشرف (حلقات، طلاب، زيارات سابقة).
/// EN: Teacher detail payload including halaqahs and visit history.
class TeacherDetail {
  final int id;
  final String name;
  final String? photoUrl;
  final String? phone;
  final List<Map<String, dynamic>> halaqahs;
  final int studentCount;
  final String? lastVisitDate;
  final int visitsCount;
  final List<PastVisit> lastVisits;
  final double? overallAvg;

  TeacherDetail.fromJson(Map<String, dynamic> j)
      : id = j['id'] as int,
        name = j['name'] as String,
        photoUrl = j['photo_url'] as String?,
        phone = j['phone'] as String?,
        halaqahs = (j['halaqahs'] as List<dynamic>)
            .map((e) => Map<String, dynamic>.from(e as Map))
            .toList(),
        studentCount = j['student_count'] as int,
        lastVisitDate = j['last_visit_date'] as String?,
        visitsCount = j['visits_count'] as int,
        lastVisits = (j['last_visits'] as List<dynamic>)
            .map((e) => PastVisit.fromJson(Map<String, dynamic>.from(e as Map)))
            .toList(),
        overallAvg = (j['overall_avg'] as num?)?.toDouble();
}

/// زيارة سابقة مبسّطة لعرضها في سجل المعلّم.
/// EN: A past supervisory visit row for lists.
class PastVisit {
  final String date;
  final int teachingScore;
  final int planScore;
  final int engagementScore;
  final double avgScore;
  final String? notes;
  final String? recommendations;

  PastVisit.fromJson(Map<String, dynamic> j)
      : date = j['date'] as String,
        teachingScore = j['teaching_skill_score'] as int,
        planScore = j['plan_adherence_score'] as int,
        engagementScore = j['student_engagement_score'] as int,
        avgScore = (j['avg_score'] as num).toDouble(),
        notes = j['notes'] as String?,
        recommendations = j['recommendations'] as String?;
}

/// سجل حلقة ليوم محدد: ملخص + صفوف الطلاب.
/// EN: Halaqah daily snapshot with per-student rows.
class HalaqahDaily {
  final String halaqahName;
  final String centerName;
  final String? teacherName;
  final String date;
  final Map<String, dynamic> summary;
  final List<StudentRecord> records;

  HalaqahDaily.fromJson(Map<String, dynamic> j)
      : halaqahName = j['halaqah_name'] as String,
        centerName = j['center_name'] as String,
        teacherName = j['teacher_name'] as String?,
        date = j['date'] as String,
        summary = Map<String, dynamic>.from(j['summary'] as Map),
        records = (j['records'] as List<dynamic>)
            .map((e) => StudentRecord.fromJson(Map<String, dynamic>.from(e as Map)))
            .toList();
}

/// صف طالب ضمن سجل حلقة يومي (حضور/أداء/حفظ).
/// EN: Single student row in supervisor halaqah daily view.
class StudentRecord {
  final int studentId;
  final String studentName;
  final String attendanceStatus;
  final String? performanceStatus;
  final String? memorizationFrom;
  final String? memorizationTo;
  final String? notes;

  StudentRecord.fromJson(Map<String, dynamic> j)
      : studentId = j['student_id'] as int,
        studentName = j['student_name'] as String,
        attendanceStatus = j['attendance_status'] as String,
        performanceStatus = j['performance_status'] as String?,
        memorizationFrom = j['memorization_from'] as String?,
        memorizationTo = j['memorization_to'] as String?,
        notes = j['notes'] as String?;
}

/// جسم طلب إنشاء زيارة إشرافية (POST `/supervisor/visits`).
/// EN: Request body for storing a new supervisory visit.
class VisitRequest {
  final int teacherId;
  final int centerId;
  final String visitDate;
  final int teachingSkillScore;
  final int planAdherenceScore;
  final int studentEngagementScore;
  final String? notes;
  final String? recommendations;

  VisitRequest({
    required this.teacherId,
    required this.centerId,
    required this.visitDate,
    required this.teachingSkillScore,
    required this.planAdherenceScore,
    required this.studentEngagementScore,
    this.notes,
    this.recommendations,
  });

  Map<String, dynamic> toJson() => {
        'teacher_id': teacherId,
        'center_id': centerId,
        'visit_date': visitDate,
        'teaching_skill_score': teachingSkillScore,
        'plan_adherence_score': planAdherenceScore,
        'student_engagement_score': studentEngagementScore,
        if (notes != null) 'notes': notes,
        if (recommendations != null) 'recommendations': recommendations,
      };
}

/// استجابة نجاح بعد حفظ زيارة (معرف الزيارة والمتوسط).
/// EN: Success payload after creating a visit.
class VisitResponse {
  final String message;
  final int visitId;
  final double avgScore;
  final String date;

  VisitResponse.fromJson(Map<String, dynamic> j)
      : message = j['message'] as String,
        visitId = j['visit_id'] as int,
        avgScore = (j['avg_score'] as num).toDouble(),
        date = j['date'] as String;
}

/// عنصر زيارة في قائمة «زياراتي» مع بيانات العرض.
/// EN: Paginated visit list item for My Visits screen.
class VisitItem {
  final int id;
  final String visitDate;
  final String teacherName;
  final String centerName;
  final int teachingScore;
  final int planScore;
  final int engagementScore;
  final double avgScore;
  final String? notes;
  final String? recommendations;

  VisitItem.fromJson(Map<String, dynamic> j)
      : id = j['id'] as int,
        visitDate = j['visit_date'] as String,
        teacherName = j['teacher_name'] as String,
        centerName = j['center_name'] as String,
        teachingScore = j['teaching_skill_score'] as int,
        planScore = j['plan_adherence_score'] as int,
        engagementScore = j['student_engagement_score'] as int,
        avgScore = (j['avg_score'] as num).toDouble(),
        notes = j['notes'] as String?,
        recommendations = j['recommendations'] as String?;
}

/// حزمة بيانات صفحة زياراتي (عناصر + معلومات الصفحات).
/// EN: My visits page bundle with pagination metadata.
class MyVisitsPageData {
  final List<VisitItem> items;
  final int total;
  final int currentPage;
  final int lastPage;

  MyVisitsPageData({
    required this.items,
    required this.total,
    required this.currentPage,
    required this.lastPage,
  });
}

/// إحصائيات الحضور الإجمالية وتفصيل الحلقات لفترة محددة.
/// EN: Attendance overview and per-halaqah breakdown.
class AttendanceStats {
  final int periodDays;
  final double overallRate;
  final List<HalaqahAttendance> halaqahs;

  AttendanceStats.fromJson(Map<String, dynamic> j)
      : periodDays = j['period_days'] as int,
        overallRate = (j['overall_rate'] as num).toDouble(),
        halaqahs = (j['halaqahs'] as List<dynamic>)
            .map((e) => HalaqahAttendance.fromJson(Map<String, dynamic>.from(e as Map)))
            .toList();
}

/// صف حضور لحلقة واحدة ضمن تقرير إحصائيات الحضور.
/// EN: Single halaqah row in attendance stats response.
class HalaqahAttendance {
  final int halaqahId;
  final String halaqahName;
  final int centerId;
  final int attendanceRate;
  final int totalRecords;
  final int presentCount;

  HalaqahAttendance.fromJson(Map<String, dynamic> j)
      : halaqahId = j['halaqah_id'] as int,
        halaqahName = j['halaqah_name'] as String,
        centerId = j['center_id'] as int,
        attendanceRate = j['attendance_rate'] as int,
        totalRecords = j['total_records'] as int,
        presentCount = j['present_count'] as int;
}
