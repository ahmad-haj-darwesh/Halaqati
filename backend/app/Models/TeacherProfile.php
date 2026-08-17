<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نموذج ملف المعلّم (TeacherProfile).
 *
 * Arabic: يحفظ بيانات إضافية تخص المعلّم (هاتف/مؤهل/تاريخ تعيين/ملاحظات/صورة) ويربطه بمستخدم
 * وبحلقة (اختياري). يوفر دوال مساعدة لاستخراج المركز/المنطقة عبر الحلقة.
 * EN: Teacher profile model storing extra teacher data and linking to user and optional halaqah.
 */
class TeacherProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'halaqah_id',
        'phone',
        'qualification',
        'hire_date',
        'notes',
        'photo_path',
    ];

    protected $casts = [
        'hire_date' => 'date',
    ];

    /**
     * المستخدم المرتبط بهذا الملف.
     * EN: Related user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الحلقة التي يعمل بها المعلّم (قد تكون null).
     * EN: Related halaqah (nullable).
     */
    public function halaqah(): BelongsTo
    {
        return $this->belongsTo(Halaqah::class);
    }

    /**
     * إرجاع مركز المعلّم عبر الحلقة.
     * EN: Helper to get the center via halaqah.
     */
    public function getCenter(): ?Center
    {
        return $this->halaqah?->center;
    }

    /**
     * إرجاع منطقة المعلّم عبر الحلقة ثم المركز.
     * EN: Helper to get the region via halaqah -> center.
     */
    public function getRegion(): ?Region
    {
        return $this->halaqah?->center?->region;
    }
}
