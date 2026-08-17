<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * نموذج الزيارة الميدانية للمشرف (SupervisorFieldVisit).
 *
 * Arabic: يمثل نموذجاً مستقلاً لزيارة ميدانية (غالباً أقدم/مختلف عن SupervisoryVisit)
 * لتقييم محاور محددة (مهارة الإعطاء/الالتزام بالخطة/تفاعل الطلاب) مع ملاحظات وتوصيات وحالة.
 * يسجل التغييرات المهمة في Activity Log عبر Spatie Activitylog.
 * EN: Field visit model capturing several score axes, notes/recommendations/status, with activity logging.
 */
class SupervisorFieldVisit extends Model
{
    use HasFactory, LogsActivity;
    protected $table = 'supervisor_field_visits';

    protected $fillable = [
        'client_uuid',
        'supervisor_user_id',
        'teacher_user_id',
        'center_id',
        'visit_date',
        'teaching_skill_score',
        'plan_adherence_score',
        'student_engagement_score',
        'notes',
        'recommendations',
        'status',
        'client_recorded_at',
        'synced_at',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'client_recorded_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    /**
     * المشرف الذي قام بالزيارة.
     * EN: Supervisor user relation.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    /**
     * المعلّم الذي تمت زيارته.
     * EN: Teacher user relation.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    /**
     * المركز المرتبط بالزيارة.
     * EN: Center relation.
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * إعدادات سجل النشاط (Activity Log).
     *
     * Arabic: يسجل فقط حقولاً محددة وعند تغيّرها فقط (dirty) مع وصف عربي للأحداث.
     * EN: Configures activity logging for key fields with localized descriptions.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'teaching_skill_score',
                'plan_adherence_score',
                'student_engagement_score',
                'notes',
                'recommendations',
                'status',
            ])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'تم إنشاء زيارة ميدانية',
                'updated' => 'تم تعديل زيارة ميدانية',
                'deleted' => 'تم حذف زيارة ميدانية',
                default => $eventName,
            });
    }
}
