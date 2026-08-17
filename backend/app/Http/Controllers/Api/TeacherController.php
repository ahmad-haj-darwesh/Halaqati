<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeacherProfile;
use App\Support\PublicMediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * نقاط نهاية خاصة بالمعلم (Teacher) لتطبيق الموبايل.
 *
 * Arabic: يوفر استرجاع معلومات الحساب (`me`) وتحديث صورة المعلم. يتعامل مع رفع الصور
 * عبر multipart أو base64 (لا سيما لـ Flutter Web).
 * EN: Teacher API endpoints for profile data and photo updates.
 */
class TeacherController extends Controller
{
    /**
     * استرجاع بيانات المعلم الحالية لعرضها في لوحة المعلّم بالموبايل.
     *
     * Arabic: يشمل صلاحية تعديل الصورة (`can_edit_own_photo`) وبيانات مكان العمل
     * (الحلقة/المركز/المنطقة) عند توفرها.
     * EN: Returns current teacher profile data for the mobile dashboard.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->teacherProfile()->with('halaqah.center.region')->first();

        $photoUrl = PublicMediaUrl::forStoragePath($profile?->photo_path);
        $canEditOwnPhoto = $user->hasPermissionTo('edit teacher own photo');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'can_edit_own_photo' => $canEditOwnPhoto,
            'profile' => $profile ? [
                'id' => $profile->id,
                'phone' => $profile->phone,
                'qualification' => $profile->qualification,
                'hire_date' => $profile->hire_date?->toDateString(),
                'photo_path' => $profile->photo_path,
                'photo_url' => $photoUrl,
                'halaqah' => $profile->halaqah ? [
                    'id' => $profile->halaqah->id,
                    'name' => $profile->halaqah->name,
                    'center' => $profile->halaqah->center ? [
                        'id' => $profile->halaqah->center->id,
                        'name' => $profile->halaqah->center->name,
                        'region' => $profile->halaqah->center->region ? [
                            'id' => $profile->halaqah->center->region->id,
                            'name' => $profile->halaqah->center->region->name,
                        ] : null,
                    ] : null,
                ] : null,
            ] : null,
        ]);
    }

    /**
     * تحديث صورة المعلم (Teacher photo).
     *
     * Arabic: يتطلب صلاحية `edit teacher own photo`. يقبل `photo` (multipart) أو
     * `photo_base64`، ويستبدل الصورة السابقة إن وجدت.
     * EN: Updates the teacher profile photo; accepts multipart or base64.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->hasPermissionTo('edit teacher own photo'), 403);

        $request->validate([
            'photo' => ['nullable', 'image', 'max:5120'],
            'photo_base64' => ['nullable', 'string', 'max:10000000'],
        ]);

        if (! $request->hasFile('photo') && ! $request->filled('photo_base64')) {
            throw ValidationException::withMessages([
                'photo' => ['يرجى اختيار صورة.'],
            ]);
        }

        $newPath = null;
        if ($request->hasFile('photo')) {
            $newPath = $request->file('photo')->store('teacher-photos/'.$user->id, 'public');
        } elseif ($request->filled('photo_base64')) {
            $newPath = $this->storeTeacherPhotoFromBase64($user->id, $request->string('photo_base64')->toString());
        }

        if ($newPath === null) {
            throw ValidationException::withMessages([
                'photo' => ['تعذّر حفظ الصورة.'],
            ]);
        }

        $profile = TeacherProfile::firstOrCreate(
            ['user_id' => $user->id],
            []
        );

        if ($profile->photo_path && Storage::disk('public')->exists($profile->photo_path)) {
            Storage::disk('public')->delete($profile->photo_path);
        }

        $profile->update(['photo_path' => $newPath]);
        $profile->refresh();

        return $this->me($request);
    }

    /**
     * تخزين صورة المعلم القادمة بصيغة base64 وإرجاع مسارها النسبي.
     *
     * Arabic: يتحقق من صحة base64، والحجم الأقصى، ونوع MIME قبل الحفظ.
     * EN: Validates and stores base64 image; returns relative storage path.
     */
    private function storeTeacherPhotoFromBase64(int $userId, string $raw): string
    {
        $raw = trim($raw);
        if (preg_match('#^data:image/(jpeg|jpg|png|gif|webp);base64,#i', $raw)) {
            $raw = substr($raw, (int) strpos($raw, ',') + 1);
        }

        $binary = base64_decode($raw, true);
        if ($binary === false) {
            throw ValidationException::withMessages([
                'photo_base64' => ['صورة غير صالحة (تشفير base64).'],
            ]);
        }

        if (strlen($binary) > 5 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'photo_base64' => ['حجم الصورة يجب ألا يتجاوز 5 ميغابايت.'],
            ]);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($binary);

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        if (! isset($allowed[$mime])) {
            throw ValidationException::withMessages([
                'photo_base64' => ['نوع الصورة غير مدعوم.'],
            ]);
        }

        $ext = $allowed[$mime];
        $relative = 'teacher-photos/'.$userId.'/'.Str::uuid()->toString().'.'.$ext;
        Storage::disk('public')->put($relative, $binary);

        return $relative;
    }
}
