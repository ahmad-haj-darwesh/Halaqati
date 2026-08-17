<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يضبط locale لوحة Filament إلى العربية فيُفعَّل RTL (الشريط الجانبي يميناً).
 *
 * Arabic: يُستخدم ضمن middleware الخاصة بلوحة Filament في `AdminPanelProvider`.
 * EN: Sets Filament admin panel locale to Arabic (enables RTL in UI).
 */
class SetFilamentArabicLocale
{
    /**
     * تنفيذ الميدلوير.
     * EN: Middleware handler.
     */
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('ar');

        return $next($request);
    }
}
