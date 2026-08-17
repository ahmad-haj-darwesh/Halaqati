<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * نموذج التسجيل (Enrollment).
 *
 * Arabic: يمثل تسجيل الطالب في حلقة مع حالة التسجيل وتواريخ الدخول/الإنهاء.
 * يفرض قيداً عند الإنشاء لمنع وجود أكثر من تسجيل نشط لنفس الطالب في نفس الوقت.
 * EN: Enrollment model representing a student's halaqah enrollment with status and dates, enforcing a single active enrollment.
 */
class Enrollment extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_GRADUATED = 'graduated';
    public const STATUS_DROPPED = 'dropped';

    protected $fillable = [
        'student_id',
        'halaqah_id',
        'enrolled_at',
        'status',
        'left_at',
        'leave_reason',
    ];

    protected $casts = [
        'enrolled_at' => 'date',
        'left_at'     => 'date',
    ];

    /**
     * الطالب المرتبط بالتسجيل.
     * EN: The enrolled student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * الحلقة المرتبطة بالتسجيل.
     * EN: The halaqah for this enrollment.
     */
    public function halaqah(): BelongsTo
    {
        return $this->belongsTo(Halaqah::class);
    }

    /**
     * Scope لإرجاع التسجيلات النشطة فقط.
     * EN: Query scope for active enrollments.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * أحداث النموذج (Model Events).
     *
     * Arabic: يتحقق عند الإنشاء من عدم وجود تسجيل نشط مسبق لنفس الطالب.
     * EN: Validates on creating that a student does not already have an active enrollment.
     *
     * @throws ValidationException عند محاولة إنشاء تسجيل نشط إضافي.
     */
    protected static function booted(): void
    {
        static::creating(function (self $enrollment) {
            if ($enrollment->status === self::STATUS_ACTIVE) {
                $exists = self::query()
                    ->where('student_id', $enrollment->student_id)
                    ->where('status', self::STATUS_ACTIVE)
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'student_id' => ['لا يمكن أن يكون للطالب أكثر من تسجيل نشط في نفس الوقت.'],
                    ]);
                }
            }
        });
    }
}

