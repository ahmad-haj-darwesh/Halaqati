<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\MobileAppRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * مصادقة تطبيق الجوال (Login/Session/Logout/FCM token).
 *
 * Arabic: يوفر نقاط نهاية لتسجيل الدخول عبر Sanctum tokens وإرجاع الدور المسموح
 * للموبايل (`MobileAppRole`). كما يسمح بتحديث توكن FCM لإشعارات Firebase.
 * EN: Mobile authentication endpoints using Sanctum tokens and role mapping.
 */
class AuthController extends Controller
{
    /**
     * تسجيل الدخول وإصدار توكن Sanctum لتطبيق الموبايل.
     *
     * Arabic: يتحقق من البيانات، ثم من كلمة المرور وحالة تفعيل الحساب، ويمنع
     * المستخدمين غير المصرّح لهم بالدخول إلى التطبيق عبر `MobileAppRole`.
     * EN: Validates credentials, ensures user is active/allowed, issues a Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'fcm_token' => ['nullable', 'string', 'max:500'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['بيانات الاعتماد غير صحيحة.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['الحساب غير مفعّل. تواصل مع المشرف.'],
            ]);
        }

        $role = MobileAppRole::resolve($user);
        if ($role === null) {
            throw ValidationException::withMessages([
                'email' => ['هذا الحساب غير مصرّح له باستخدام التطبيق.'],
            ]);
        }

        if ($request->filled('fcm_token')) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
            ],
        ]);
    }

    /**
     * استعادة الدور بعد إعادة تشغيل التطبيق (عند وجود توكن فقط).
     *
     * Arabic: تستخدم عندما يكون التوكن موجوداً محلياً لكن الدور غير محفوظ أو فقد.
     * EN: Used to restore role/user info when only a token exists on the device.
     */
    public function session(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = MobileAppRole::resolve($user);
        if ($role === null) {
            return response()->json([
                'message' => 'هذا الحساب غير مصرّح له باستخدام التطبيق.',
            ], 403);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
            ],
        ]);
    }

    /**
     * تسجيل الخروج وإبطال التوكن الحالي.
     *
     * Arabic: يحذف `currentAccessToken` فقط (لا يلغي جميع جلسات المستخدم).
     * EN: Deletes only the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج بنجاح.']);
    }

    /**
     * تحديث توكن FCM للمستخدم الحالي.
     *
     * Arabic: يُستخدم لتحديث التوكن عند تغيّره على الجهاز لتستمر الإشعارات بالعمل.
     * EN: Updates the user's FCM token for push notifications.
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => ['required', 'string', 'max:500'],
        ]);

        $request->user()->update(['fcm_token' => $request->fcm_token]);

        return response()->json(['message' => 'تم تحديث رمز الإشعارات']);
    }
}
