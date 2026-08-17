<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * نموذج محور/معيار الاختبار (TestRubric).
 *
 * Arabic: يمثل محور تقييم ضمن اختبار معيّن مع درجة قصوى (max_score) ووزن (weight)
 * وترتيب عرض (sort_order). ترتبط به عناصر نتائج (`TestResultItem`) لتسجيل الدرجة على هذا المحور.
 * EN: Test rubric axis for a test with max score, weight and ordering, linked to result items.
 */
class TestRubric extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'name',
        'max_score',
        'weight',
        'sort_order',
    ];

    protected $casts = [
        'weight' => 'decimal:3',
    ];

    /**
     * الاختبار الذي ينتمي إليه هذا المحور.
     * EN: Parent test relation.
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    /**
     * عناصر النتائج المرتبطة بهذا المحور.
     * EN: Result items for this rubric axis.
     */
    public function resultItems(): HasMany
    {
        return $this->hasMany(TestResultItem::class);
    }
}

