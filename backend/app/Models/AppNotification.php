<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نموذج إشعار التطبيق (AppNotification).
 *
 * Arabic: يخزن إشعارات داخلية للمستخدمين (عنوان/نص/نوع/بيانات إضافية JSON) مع تتبع حالة القراءة (`read_at`).
 * يُستخدم عادةً بالتوازي مع إشعارات FCM.
 * EN: In-app notification model with title/body/type/payload and read tracking.
 */
class AppNotification extends Model
{
    use HasFactory;

    protected $table = 'app_notifications';

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'type',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    /**
     * المستخدم المستلم للإشعار.
     * EN: Recipient user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeUnread(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('read_at');
    }
}
