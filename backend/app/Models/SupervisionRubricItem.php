<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * نموذج محور/عنصر قالب الإشراف (SupervisionRubricItem).
 *
 * Arabic: يمثل محور تقييم داخل قالب إشراف مع مفتاح (key) واسم عرض (label) وحد أعلى للدرجة،
 * وترتيب و حالة تفعيل. يرتبط بدرجات الزيارات (visitScores) التي تستخدم هذا المحور.
 * EN: Rubric item model (key/label/max_score/sort_order/active) linked to supervisory visit scores.
 */
class SupervisionRubricItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'supervision_rubric_id',
        'key',
        'label',
        'max_score',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * القالب (Rubric) الذي ينتمي إليه هذا المحور.
     * EN: Parent rubric relation.
     */
    public function rubric(): BelongsTo
    {
        return $this->belongsTo(SupervisionRubric::class, 'supervision_rubric_id');
    }

    /**
     * درجات الزيارات المرتبطة بهذا المحور.
     * EN: Visit scores using this rubric item.
     */
    public function visitScores(): HasMany
    {
        return $this->hasMany(SupervisoryVisitScore::class, 'supervision_rubric_item_id');
    }
}

