<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نموذج سجل الحضور (AttendanceRecord).
 *
 * Arabic: يمثل حالة حضور الطالب في تاريخ معيّن ضمن حلقة محددة، مع إمكانية حفظ ملاحظات
 * وتسجيل المستخدم الذي أدخل السجل (`recorded_by_user_id`).
 * EN: Attendance record model for a student's status on a given date within a halaqah, including recorded-by tracking.
 */
class AttendanceRecord extends Model
{
    use HasFactory;

    public const STATUS_PRESENT = 'present';
    public const STATUS_EXCUSED = 'excused_absence';
    public const STATUS_UNEXCUSED = 'unexcused_absence';

    protected $fillable = [
        'halaqah_id',
        'student_id',
        'date',
        'status',
        'recorded_by_user_id',
        'notes',
        'client_recorded_at',
        'synced_at',
    ];

    protected $casts = [
        'client_recorded_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    /**
     * الحلقة المرتبطة بالسجل.
     * EN: Related halaqah.
     */
    public function halaqah(): BelongsTo
    {
        return $this->belongsTo(Halaqah::class);
    }

    /**
     * الطالب المرتبط بالسجل.
     * EN: Related student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * المستخدم الذي سجّل الحضور/الغياب.
     * EN: User who recorded this attendance record.
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}

