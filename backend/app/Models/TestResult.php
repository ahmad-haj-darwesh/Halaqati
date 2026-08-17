<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * نموذج نتيجة الاختبار (TestResult).
 *
 * Arabic: يمثل نتيجة تعيين اختبار لطالب، ويخزن الدرجات (حفظ/تجويد/مراجعة) والمجموع
 * والمستوى وملاحظات وتاريخ الاختبار. كما يقوم بتسجيل تغييرات محددة في سجل النشاط
 * عبر Spatie Activitylog.
 * EN: Test result model storing scores/level/notes and logging key changes via Spatie Activitylog.
 */
class TestResult extends Model
{
    use HasFactory;
    use LogsActivity;

    public const LEVEL_EXCELLENT = 'excellent';
    public const LEVEL_GOOD = 'good';
    public const LEVEL_ACCEPTABLE = 'acceptable';
    public const LEVEL_WEAK = 'weak';

    protected $fillable = [
        'test_assignment_id',
        'examiner_user_id',
        'memorization_score',
        'tajweed_score',
        'review_score',
        'tested_surah',
        'total_score',
        'level',
        'notes',
        'tested_at',
        'client_recorded_at',
        'synced_at',
    ];

    protected $casts = [
        'total_score' => 'decimal:2',
        'tested_at' => 'datetime',
        'client_recorded_at' => 'datetime',
        'synced_at' => 'datetime',
        'memorization_score' => 'integer',
        'tajweed_score' => 'integer',
        'review_score' => 'integer',
    ];

    /**
     * التعيين المرتبط بهذه النتيجة.
     * EN: Related assignment.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TestAssignment::class, 'test_assignment_id');
    }

    /**
     * المختبر (Examiner) الذي أدخل/سجل النتيجة.
     * EN: Examiner user relation.
     */
    public function examiner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'examiner_user_id');
    }

    /**
     * عناصر النتيجة التفصيلية حسب المحاور (إن كانت مستخدمة).
     * EN: Detailed result items per rubric axis (if used).
     */
    public function items(): HasMany
    {
        return $this->hasMany(TestResultItem::class);
    }

    /**
     * إعدادات سجل النشاط (Activity Log).
     *
     * Arabic: يسجل فقط حقولاً محددة وعند تغيّرها فقط (dirty)، مع وصف عربي للأحداث.
     * EN: Configures activity logging (selected fields, only dirty, localized descriptions).
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['memorization_score', 'tajweed_score', 'review_score', 'total_score', 'level', 'notes'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'تم إنشاء نتيجة اختبار',
                'updated' => 'تم تعديل نتيجة اختبار',
                'deleted' => 'تم حذف نتيجة اختبار',
                default => $eventName,
            });
    }
}

