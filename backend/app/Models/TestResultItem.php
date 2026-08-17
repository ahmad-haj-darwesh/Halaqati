<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نموذج عنصر نتيجة الاختبار (TestResultItem).
 *
 * Arabic: يمثل بنداً تفصيلياً ضمن نتيجة اختبار مرتبط بمحور/معيار (`TestRubric`) مع درجة وملاحظات.
 * لا يستخدم timestamps لتقليل الضجيج في التخزين.
 * EN: Detailed result item linked to a rubric axis with score and notes; timestamps disabled.
 */
class TestResultItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'test_result_id',
        'test_rubric_id',
        'score',
        'notes',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    /**
     * النتيجة الأم (TestResult).
     * EN: Parent result relation.
     */
    public function result(): BelongsTo
    {
        return $this->belongsTo(TestResult::class, 'test_result_id');
    }

    /**
     * محور/معيار الاختبار (TestRubric) الذي ينتمي إليه هذا العنصر.
     * EN: Related test rubric relation.
     */
    public function rubric(): BelongsTo
    {
        return $this->belongsTo(TestRubric::class, 'test_rubric_id');
    }
}

