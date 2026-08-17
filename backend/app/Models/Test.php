<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * نموذج الاختبار (Test).
 *
 * Arabic: يمثل اختباراً (عادي أو عينات) مع نطاق اختياري (منطقة/مركز/حلقة) وتاريخ/نشر،
 * وإعدادات أخذ العينات (strategy/count/percent/seed/active_only). يرتبط بتعيينات (assignments)
 * وبمعايير/محاور الاختبار (rubrics) إن كانت موجودة.
 * EN: Test model supporting regular/sampling types with optional scope and sampling settings, linked to assignments and rubrics.
 */
class Test extends Model
{
    use HasFactory;

    public const TYPE_REGULAR = 'regular';
    public const TYPE_SAMPLING = 'sampling';

    protected $fillable = [
        'type',
        'title',
        'description',
        'scope_halaqah_id',
        'scope_center_id',
        'scope_region_id',
        'scheduled_at',
        'created_by_user_id',
        'is_published',
        'sampling_strategy',
        'sampling_count',
        'sampling_percent',
        'sampling_seed',
        'sampling_active_only',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sampling_active_only' => 'boolean',
        'scheduled_at' => 'datetime',
        'sampling_percent' => 'decimal:2',
        'sampling_seed' => 'integer',
    ];

    /**
     * المستخدم الذي أنشأ الاختبار.
     * EN: Creator user relationship.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * الحلقة التي يقتصر عليها نطاق الاختبار (إن وُجدت).
     * EN: Optional scope halaqah.
     */
    public function scopeHalaqah(): BelongsTo
    {
        return $this->belongsTo(Halaqah::class, 'scope_halaqah_id');
    }

    /**
     * المركز الذي يقتصر عليه نطاق الاختبار (إن وُجد).
     * EN: Optional scope center.
     */
    public function scopeCenter(): BelongsTo
    {
        return $this->belongsTo(Center::class, 'scope_center_id');
    }

    /**
     * المنطقة التي يقتصر عليها نطاق الاختبار (إن وُجدت).
     * EN: Optional scope region.
     */
    public function scopeRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'scope_region_id');
    }

    /**
     * محاور/معايير الاختبار (إن كانت مستخدمة).
     * EN: Test rubrics (if used).
     */
    public function rubrics(): HasMany
    {
        return $this->hasMany(TestRubric::class);
    }

    /**
     * تعيينات الاختبار للطلاب.
     * EN: Assignments for this test.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TestAssignment::class);
    }
}

