<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * نموذج الطالب (Student).
 *
 * Arabic: يمثل بيانات الطالب الأساسية وبيانات ولي الأمر وقفل الملف والصورة، ويرتبط
 * بسجلات التسجيل (Enrollments) والحضور (Attendance) والتقييمات اليومية والحفظ والاختبارات.
 * EN: Student model holding profile data and relations to enrollments, attendance, evaluations, memorization, and tests.
 */
class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'gender',
        'birth_date',
        'guardian_name',
        'guardian_phone',
        'national_id',
        'notes',
        'is_active',
        'photo_path',
        'profile_locked',
        'teacher_may_edit_profile',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_active'  => 'boolean',
        'profile_locked' => 'boolean',
        'teacher_may_edit_profile' => 'boolean',
    ];

    /**
     * جميع تسجيلات الطالب عبر الزمن.
     * EN: All enrollments for the student.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * التسجيل الحالي (النشط) للطالب.
     *
     * Arabic: يعتمد على `Enrollment::STATUS_ACTIVE` ويختار الأحدث حسب `enrolled_at`.
     * EN: Current active enrollment (latest by enrolled_at).
     */
    public function currentEnrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->latestOfMany('enrolled_at');
    }

    /**
     * الحلقات المرتبط بها الطالب عبر جدول التسجيلات (Pivot: enrollments).
     * EN: Halaqahs related through enrollments pivot.
     */
    public function halaqahs(): BelongsToMany
    {
        return $this->belongsToMany(Halaqah::class, 'enrollments')
            ->withPivot(['enrolled_at', 'status', 'left_at', 'leave_reason'])
            ->withTimestamps();
    }

    /**
     * سجلات الحضور الخاصة بالطالب.
     * EN: Attendance records for the student.
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * التقييمات اليومية الخاصة بالطالب.
     * EN: Daily evaluations for the student.
     */
    public function dailyEvaluations(): HasMany
    {
        return $this->hasMany(DailyEvaluation::class);
    }

    /**
     * سجلات الحفظ/المراجعة الخاصة بالطالب.
     * EN: Memorization entries for the student.
     */
    public function memorizationEntries(): HasMany
    {
        return $this->hasMany(MemorizationEntry::class);
    }

    /**
     * تعيينات الاختبارات الخاصة بالطالب.
     * EN: Test assignments for the student.
     */
    public function testAssignments(): HasMany
    {
        return $this->hasMany(TestAssignment::class);
    }

    /**
     * نتائج الاختبارات المرتبطة بتعيينات هذا الطالب.
     *
     * Arabic: علاقة `HasManyThrough` عبر `TestAssignment`.
     * EN: HasManyThrough relationship via TestAssignment.
     */
    public function testResults(): HasManyThrough
    {
        return $this->hasManyThrough(
            TestResult::class,
            TestAssignment::class,
            'student_id',
            'test_assignment_id',
            'id',
            'id'
        );
    }

    /**
     * جميع طلبات تعديل/مراجعة ملف الطالب (Submissions).
     * EN: Profile submission requests for the student.
     */
    public function profileSubmissions(): HasMany
    {
        return $this->hasMany(StudentProfileSubmission::class);
    }

    /**
     * طلب المراجعة المعلّق (قيد المراجعة) إن وُجد.
     * EN: Pending profile submission, if any.
     */
    public function pendingProfileSubmission(): HasOne
    {
        return $this->hasOne(StudentProfileSubmission::class)
            ->where('status', StudentProfileSubmission::STATUS_PENDING);
    }
}

