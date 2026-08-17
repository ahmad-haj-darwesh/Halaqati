<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * نموذج سبب التقييم (EvaluationReason).
 *
 * Arabic: يمثل سبباً معياريًا يُستخدم كتفسير للتميّز أو التقصير في التقييم اليومي،
 * ويمكن تفعيله/تعطيله وترتيبه للعرض.
 * EN: Evaluation reason model used in daily evaluations, with type (excellence/deficiency), active flag, and sort ordering.
 */
class EvaluationReason extends Model
{
    use HasFactory;

    public const TYPE_EXCELLENCE = 'excellence';
    public const TYPE_DEFICIENCY = 'deficiency';

    protected $fillable = [
        'key',
        'label',
        'type',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * التقييمات اليومية المرتبطة بهذا السبب.
     * EN: Daily evaluations related to this reason.
     */
    public function dailyEvaluations(): BelongsToMany
    {
        return $this->belongsToMany(DailyEvaluation::class, 'daily_evaluation_reason');
    }
}

