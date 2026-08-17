<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * نموذج الزيارة الإشرافية (SupervisoryVisit).
 *
 * Arabic: يمثل زيارة يقوم بها مشرف/موجّه لحلقة/مركز لتقييم أداء المعلّم وفق قالب تقييم (Rubric)،
 * ويخزن ملخصاً وتوصيات وحالة الاعتماد/الإقفال (Finalize). يحتوي أيضاً على درجات تفصيلية (scores)
 * ومرفقات (attachments).
 * EN: Supervisory visit model capturing rubric-based evaluation, summary/recommendations, finalized state, scores and attachments.
 */
class SupervisoryVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'supervision_rubric_id',
        'supervisor_user_id',
        'center_id',
        'halaqah_id',
        'teacher_user_id',
        'visited_at',
        'duration_minutes',
        'overall_level',
        'overall_score',
        'summary',
        'recommendations',
        'is_finalized',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'is_finalized' => 'boolean',
        'overall_score' => 'decimal:2',
    ];

    /**
     * قالب التقييم (Rubric) المستخدم في الزيارة.
     * EN: Related supervision rubric.
     */
    public function rubric(): BelongsTo
    {
        return $this->belongsTo(SupervisionRubric::class, 'supervision_rubric_id');
    }

    /**
     * المستخدم المشرف (Supervisor) الذي قام بالزيارة.
     * EN: Related supervisor user.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    /**
     * المركز الذي تمت فيه الزيارة.
     * EN: Related center.
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * الحلقة التي تمت فيها الزيارة.
     * EN: Related halaqah.
     */
    public function halaqah(): BelongsTo
    {
        return $this->belongsTo(Halaqah::class);
    }

    /**
     * المعلّم الذي تم تقييمه في الزيارة.
     * EN: Related teacher user.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    /**
     * درجات الزيارة التفصيلية حسب محاور القالب.
     * EN: Detailed visit scores (per rubric item).
     */
    public function scores(): HasMany
    {
        return $this->hasMany(SupervisoryVisitScore::class);
    }

    /**
     * المرفقات المرتبطة بالزيارة.
     * EN: Visit attachments.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(SupervisoryVisitAttachment::class);
    }

    /**
     * إعادة حساب الدرجة الإجمالية للزيارة من مجموع الدرجات التفصيلية.
     *
     * Arabic: يُحدّث الخاصية `overall_score` في الذاكرة فقط (لا يقوم بالحفظ تلقائياً).
     * EN: Recomputes overall_score in-memory (does not save automatically).
     */
    public function recomputeOverallScore(): void
    {
        $sum = $this->scores()->sum(DB::raw('COALESCE(score, 0)'));
        $this->overall_score = $sum;
    }
}

