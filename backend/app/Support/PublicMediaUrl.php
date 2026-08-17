<?php

namespace App\Support;

/**
 * روابط ملفات التخزين العامة عبر /api/* لتطبيق CORS على Flutter Web
 * (الملفات المباشرة تحت /storage لا تمرّ بميدلوير Laravel).
 *
 * Arabic: تُستخدم هذه الدالة لحل مسار نسبي داخل `storage/app/public` إلى رابط
 * عام يمر عبر Route داخل Laravel لتطبيق الميدلوير و/أو CORS عند الحاجة.
 * EN: Resolves a storage relative path to a public URL routed through Laravel.
 */
final class PublicMediaUrl
{
    /**
     * بناء رابط عام لصورة/ملف مخزّن بناءً على المسار النسبي.
     *
     * Arabic: يعيد `null` إذا كان المسار فارغاً.
     * EN: Returns null when the path is empty.
     */
    public static function forStoragePath(?string $relativePath): ?string
    {
        if ($relativePath === null || $relativePath === '') {
            return null;
        }

        return url('/api/student-photo?'.http_build_query(['path' => $relativePath]));
    }
}
