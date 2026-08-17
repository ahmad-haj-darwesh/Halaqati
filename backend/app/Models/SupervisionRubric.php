<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * نموذج قالب الإشراف (SupervisionRubric).
 *
 * Arabic: يمثل قالب تقييم يُستخدم في الزيارات الإشرافية، ويتكون من محاور/عناصر (items)
 * مرتبة حسب `sort_order`، مع حالة تفعيل، وربط بالمستخدم الذي أنشأ القالب.
 * EN: Supervision rubric model composed of ordered rubric items, with active flag and creator tracking.
 */
class SupervisionRubric extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * محاور/عناصر القالب (مرتبة).
     * EN: Ordered rubric items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SupervisionRubricItem::class)->orderBy('sort_order');
    }

    /**
     * المستخدم الذي أنشأ القالب.
     * EN: Creator user relation.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}

