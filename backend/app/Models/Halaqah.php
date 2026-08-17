<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * نموذج الحلقة (Halaqah).
 *
 * Arabic: يمثل حلقة ضمن مركز (Center) وقد يكون لها معلّم معيّن (TeacherProfile)،
 * ويرتبط بتسجيلات الطلاب والحضور والتقييمات اليومية والحفظ.
 * EN: Halaqah model linked to a center, an optional teacher profile, and various student activity records.
 */
class Halaqah extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'center_id', 'description', 'capacity'];

    /**
     * المركز الذي تتبع له الحلقة.
     * EN: The center this halaqah belongs to.
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * ملف المعلّم المعيّن للحلقة (إن وُجد).
     * EN: Assigned teacher profile (if any).
     */
    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    /**
     * تسجيلات الطلاب ضمن الحلقة.
     * EN: Enrollments in this halaqah.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * الطلاب المرتبطون بهذه الحلقة عبر التسجيلات.
     * EN: Students through enrollments.
     */
    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(Student::class, Enrollment::class, 'halaqah_id', 'id', 'id', 'student_id');
    }

    /**
     * سجلات الحضور لهذه الحلقة.
     * EN: Attendance records for this halaqah.
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * التقييمات اليومية لهذه الحلقة.
     * EN: Daily evaluations for this halaqah.
     */
    public function dailyEvaluations(): HasMany
    {
        return $this->hasMany(DailyEvaluation::class);
    }

    /**
     * سجلات الحفظ/المراجعة لهذه الحلقة.
     * EN: Memorization entries for this halaqah.
     */
    public function memorizationEntries(): HasMany
    {
        return $this->hasMany(MemorizationEntry::class);
    }

    /**
     * إرجاع المنطقة (Region) عبر علاقة المركز.
     *
     * Arabic: Accessor لتسهيل الوصول إلى `$halaqah->region`.
     * EN: Convenience accessor for the halaqah's region through its center.
     */
    public function getRegionAttribute(): ?Region
    {
        return $this->center?->region;
    }
}
