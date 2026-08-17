<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * نموذج المركز (Center).
 *
 * Arabic: يمثل مركزاً ضمن منطقة (Region) وقد يرتبط بمستخدم مدير (`admin_user_id`).
 * يضم عدة حلقات (Halaqahs) ويستخدم بكثرة في نطاق الصلاحيات (managed centers).
 * EN: Center model belonging to a region with an optional admin user, and containing many halaqahs.
 */
class Center extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'region_id', 'admin_user_id', 'address', 'phone'];

    /**
     * المنطقة التي يتبع لها المركز.
     * EN: Related region.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * المستخدم المدير للمركز (Admin).
     * EN: Related admin user.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * الحلقات التابعة للمركز.
     * EN: Halaqahs in this center.
     */
    public function halaqahs(): HasMany
    {
        return $this->hasMany(Halaqah::class);
    }
}
