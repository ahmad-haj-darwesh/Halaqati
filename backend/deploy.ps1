# سكريبت تحضير Backend للنشر على InfinityFree
# PowerShell Script

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "تحضير مشروع حلقتي للنشر" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. التحقق من وجود ملف .env.production
if (-not (Test-Path ".env.production")) {
    Write-Host "⚠️  ملف .env.production غير موجود" -ForegroundColor Yellow
    Write-Host "يرجى نسخ .env.production.example إلى .env.production وتعديله" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "الأمر:" -ForegroundColor Green
    Write-Host "Copy-Item .env.production.example .env.production" -ForegroundColor Gray
    exit 1
}

Write-Host "✅ ملف .env.production موجود" -ForegroundColor Green

# 2. إنشاء مجلد النشر
$deployDir = "deploy_output"
if (Test-Path $deployDir) {
    Remove-Item $deployDir -Recurse -Force
}
New-Item -ItemType Directory -Path $deployDir | Out-Null
Write-Host "✅ تم إنشاء مجلد النشر" -ForegroundColor Green

# 3. نسخ الملفات المطلوبة
Write-Host "📦 نسخ الملفات..." -ForegroundColor Cyan

# نسخ مجلد public بالكامل
Copy-Item -Path "public" -Destination "$deployDir/public" -Recurse
Write-Host "  ✅ public/" -ForegroundColor Gray

# نسخ مجلد app
Copy-Item -Path "app" -Destination "$deployDir/app" -Recurse
Write-Host "  ✅ app/" -ForegroundColor Gray

# نسخ مجلد config
Copy-Item -Path "config" -Destination "$deployDir/config" -Recurse
Write-Host "  ✅ config/" -ForegroundColor Gray

# نسخ مجلد database (migrations فقط)
New-Item -ItemType Directory -Path "$deployDir/database/migrations" -Force | Out-Null
Copy-Item -Path "database/migrations/*" -Destination "$deployDir/database/migrations" -Recurse
Write-Host "  database/migrations/" -ForegroundColor Gray

# نسخ مجلد bootstrap
Copy-Item -Path "bootstrap" -Destination "$deployDir/bootstrap" -Recurse
Write-Host "  ✅ bootstrap/" -ForegroundColor Gray

# نسخ مجلد resources
Copy-Item -Path "resources" -Destination "$deployDir/resources" -Recurse
Write-Host "  ✅ resources/" -ForegroundColor Gray

# نسخ مجلد routes
Copy-Item -Path "routes" -Destination "$deployDir/routes" -Recurse
Write-Host "  ✅ routes/" -ForegroundColor Gray

# نسخ مجلد storage
Copy-Item -Path "storage" -Destination "$deployDir/storage" -Recurse
Write-Host "  ✅ storage/" -ForegroundColor Gray

# نسخ مجلد vendor (إذا كان موجوداً)
if (Test-Path "vendor") {
    Copy-Item -Path "vendor" -Destination "$deployDir/vendor" -Recurse
    Write-Host "  ✅ vendor/" -ForegroundColor Gray
} else {
    Write-Host "  ⚠️  vendor/ غير موجود - ستحتاج لتشغيل composer install على الخادم" -ForegroundColor Yellow
}

# نسخ ملفات الجذر
Copy-Item -Path "artisan" -Destination "$deployDir/artisan"
Copy-Item -Path "composer.json" -Destination "$deployDir/composer.json"
Copy-Item -Path "composer.lock" -Destination "$deployDir/composer.lock"
Write-Host "  ✅ artisan, composer.json, composer.lock" -ForegroundColor Gray

# نسخ ملف .env.production كـ .env
Copy-Item -Path ".env.production" -Destination "$deployDir/.env"
Write-Host "  ✅ .env" -ForegroundColor Gray

# 4. إنشاء ملف .htaccess
$htaccessContent = @"
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ /public/$1 [L]
</IfModule>
"@
Set-Content -Path "$deployDir/.htaccess" -Value $htaccessContent
Write-Host "  ✅ .htaccess" -ForegroundColor Gray

# 5. إنشاء ملفات index.php
$indexContent = @"
<?php

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

\$app = require_once __DIR__.'/bootstrap/app.php';

\$kernel = \$app->make(Illuminate\Contracts\Http\Kernel::class);

\$response = \$kernel->handle(
    \$request = Illuminate\Http\Request::capture()
);

\$response->send();

\$kernel->terminate(\$request, \$response);
"@
Set-Content -Path "$deployDir/index.php" -Value $indexContent
Write-Host "  ✅ index.php" -ForegroundColor Gray

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✅ تم تحضير الملفات للنشر" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "المجلد: $deployDir" -ForegroundColor Yellow
Write-Host ""
Write-Host "الخطوات التالية:" -ForegroundColor Cyan
Write-Host "1. ارفع محتوى مجلد $deployDir إلى InfinityFree عبر FTP" -ForegroundColor White
Write-Host "2. تأكد من وجود firebase-credentials.json في storage/app/" -ForegroundColor White
Write-Host "3. شغّل php artisan migrate على الخادم (إذا أمكن)" -ForegroundColor White
Write-Host "4. أو استورد قاعدة البيانات يدوياً عبر phpMyAdmin" -ForegroundColor White
Write-Host ""
