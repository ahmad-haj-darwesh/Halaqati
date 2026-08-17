<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * نموذج المستخدم (User).
 *
 * Arabic: يمثل حساب المستخدم في النظام ويستخدم:
 * - Laravel Sanctum للتوثيق عبر API\n+ * - Spatie Roles/Permissions للأدوار\n+ * - Filament للوصول إلى لوحة الإدارة (مع قيود محددة)
 *
 * EN: Application user model with Sanctum tokens, role management, and Filament admin access rules.
 */
class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'fcm_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    /**
     * السماح بالوصول إلى لوحة Filament.
     *
     * Arabic: يمنع دخول حسابات Teacher إلى لوحة الإدارة، ويشترط أن يكون الحساب مفعّلاً.
     * EN: Allows Filament panel access only for active non-Teacher users.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && ! $this->hasRole('Teacher');
    }

    /**
     * ملف المعلّم المرتبط بالمستخدم (إن كان Teacher).
     * EN: Teacher profile relation, if applicable.
     */
    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    /**
     * المراكز التي يديرها المستخدم (Admin Centers).
     *
     * Arabic: تُستخدم لتحديد نطاق الصلاحيات (User Scope) في كثير من السياسات والاستعلامات.
     * EN: Centers managed by the user, used for scoping permissions and queries.
     */
    public function managedCenters(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Center::class, 'admin_user_id');
    }
}
