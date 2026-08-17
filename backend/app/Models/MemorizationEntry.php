<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نموذج سجل الحفظ/المراجعة (MemorizationEntry).
 *
 * Arabic: يمثل ما أنجزه الطالب من حفظ ومراجعة في يوم محدد ضمن حلقة،
 * مع إمكانية توثيق الأخطاء.
 * EN: Memorization/revision entry for a student on a specific date within a halaqah.
 */
class MemorizationEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'halaqah_id',
        'student_id',
        'date',
        'memorization_from',
        'memorization_to',
        'revision_from',
        'revision_to',
        'mistakes',
        'client_recorded_at',
        'synced_at',
    ];

    protected $casts = [
        'client_recorded_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    /**
     * الحلقة المرتبط بها السجل.
     * EN: Related halaqah.
     */
    public function halaqah(): BelongsTo
    {
        return $this->belongsTo(Halaqah::class);
    }

    /**
     * الطالب المرتبط به السجل.
     * EN: Related student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}

