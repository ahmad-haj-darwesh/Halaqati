<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;

/**
 * خدمة إرسال الإشعارات (تخزين + FCM).
 *
 * Arabic: تسجّل الإشعار في قاعدة البيانات (`AppNotification`) ثم تحاول إرساله عبر
 * Firebase Cloud Messaging عند توفر التوكن والاعتماديات وبشرط بيئة الإنتاج.
 * EN: Persists a notification and optionally sends it via FCM.
 */
class NotificationService
{
    /**
     * إرسال إشعار لمستخدم واحد اعتماداً على FCM token.
     *
     * Arabic: تُعيد `false` إذا لا يوجد توكن أو إذا كانت بيئة غير production أو
     * لا توجد بيانات اعتماد Firebase صالحة.
     * EN: Returns false when token/credentials/environment prevent sending.
     *
     * @param  User  $user  المستخدم المستهدف
     * @param  string  $title  عنوان الإشعار
     * @param  string  $body  محتوى الإشعار
     * @param  array<string,mixed>  $data  حمولة بيانات إضافية (ستحوّل إلى strings لـ FCM)
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        $merged = array_merge($data, ['click_action' => 'FLUTTER_NOTIFICATION_CLICK']);
        $type = $merged['type'] ?? 'general';

        AppNotification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'type' => is_string($type) ? $type : 'general',
            'data' => $merged,
        ]);

        if (empty($user->fcm_token)) {
            return false;
        }

        if (! $this->shouldSendFcm()) {
            Log::info('FCM skipped (non-production or no credentials)', ['user_id' => $user->id]);

            return false;
        }

        $stringData = $this->stringifyDataPayload($merged);

        try {
            $message = CloudMessage::new()
                ->withToken($user->fcm_token)
                ->withNotification(Notification::create($title, $body))
                ->withData($stringData);

            Firebase::messaging()->send($message);

            return true;
        } catch (\Throwable $e) {
            Log::error("FCM send failed for user {$user->id}: ".$e->getMessage());

            if ($this->isInvalidTokenError($e) && $user->fcm_token) {
                $user->update(['fcm_token' => null]);
            }

            return false;
        }
    }

    /**
     * إرسال إشعار لقائمة مستخدمين.
     *
     * Arabic: تجمع النتائج في شكل `{sent, failed}`. القيم الفعلية تعتمد على توفر
     * توكنات صالحة وبيئة الإنتاج واعتماديات Firebase.
     * EN: Sends to many users and returns aggregated results.
     *
     * @param  array<int, User>  $users
     * @return array{sent: int, failed: int}
     */
    public function sendToUsers(array $users, string $title, string $body, array $data = []): array
    {
        $results = ['sent' => 0, 'failed' => 0];

        foreach ($users as $user) {
            $this->sendToUser($user, $title, $body, $data)
                ? $results['sent']++
                : $results['failed']++;
        }

        return $results;
    }

    /**
     * إرسال إشعار لكل المستخدمين ضمن دور محدد (Spatie Roles).
     *
     * Arabic: يقتصر على المستخدمين النشطين ممن لديهم `fcm_token`.
     * EN: Targets active users with FCM tokens by role.
     *
     * @return array{sent: int, failed: int}
     */
    public function sendToRole(string $role, string $title, string $body, array $data = []): array
    {
        $users = User::role($role)
            ->whereNotNull('fcm_token')
            ->where('is_active', true)
            ->get();

        return $this->sendToUsers($users->all(), $title, $body, $data);
    }

    private function shouldSendFcm(): bool
    {
        if (! app()->environment('production')) {
            return false;
        }

        $project = (string) config('firebase.default', 'app');
        $path = config("firebase.projects.{$project}.credentials");

        return is_string($path) && $path !== '' && is_file($path);
    }

    private function isInvalidTokenError(\Throwable $e): bool
    {
        $msg = $e->getMessage();

        if (str_contains($msg, 'registration-token-not-registered')) {
            return true;
        }

        return str_contains(strtolower($msg), 'not registered');
    }

    /**
     * FCM data payload: all values must be strings.
     *
     * Arabic: FCM يشترط أن تكون قيم `data` نصية؛ هذه الدالة تحوّل القيم المعقدة
     * إلى JSON لتجنّب فشل الإرسال.
     * EN: Converts payload values into strings (complex values become JSON).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function stringifyDataPayload(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $k = (string) $key;
            if (is_scalar($value) || $value === null) {
                $out[$k] = $value === null ? '' : (string) $value;
            } else {
                $out[$k] = json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
            }
        }

        return $out;
    }
}
