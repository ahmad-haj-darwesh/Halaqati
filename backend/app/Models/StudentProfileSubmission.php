<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نموذج طلب مراجعة/اعتماد ملف الطالب (StudentProfileSubmission).
 *
 * Arabic: يمثل نسخة من بيانات ملف الطالب التي يرفعها المعلّم للمراجعة، مع حالة الطلب
 * (pending/approved/rejected) وتتبع من راجع الطلب ومتى وما هي ملاحظة المراجع.
 * يتم عادةً إنشاء الطلب من تطبيق الموبايل ثم مراجعته من لوحة الإدارة.
 * EN: Student profile submission request created by a teacher and reviewed in admin panel.
 */
class StudentProfileSubmission extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'student_id',
        'teacher_user_id',
        'status',
        'full_name',
        'gender',
        'birth_date',
        'guardian_name',
        'guardian_phone',
        'national_id',
        'notes',
        'photo_path',
        'reviewed_by_user_id',
        'reviewed_at',
        'reviewer_note',
    ];

    protected function casts(): array
    {
        return [
            'birth_date'   => 'date',
            'reviewed_at'  => 'datetime',
        ];
    }

    /**
     * الطالب صاحب الطلب.
     * EN: Related student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * المعلّم الذي رفع الطلب.
     * EN: Teacher who submitted the request.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    /**
     * المستخدم الذي راجع الطلب (إن وُجد).
     * EN: Reviewer user relation.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
