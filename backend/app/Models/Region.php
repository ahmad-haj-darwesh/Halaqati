<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * نموذج المنطقة (Region).
 *
 * Arabic: يمثل نطاقاً جغرافياً يضم عدة مراكز، ويمكن الوصول للحلقات عبر علاقة (HasManyThrough).
 * EN: Region model that contains many centers and provides a through relationship to halaqahs.
 */
class Region extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    /**
     * المراكز التابعة للمنطقة.
     * EN: Centers in this region.
     */
    public function centers(): HasMany
    {
        return $this->hasMany(Center::class);
    }

    /**
     * الحلقات ضمن المنطقة عبر المراكز.
     * EN: Halaqahs through centers.
     */
    public function halaqahs(): HasManyThrough
    {
        return $this->hasManyThrough(Halaqah::class, Center::class);
    }
}
