# سكريبت بناء تطبيق Flutter للإنتاج
# PowerShell Script

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "بناء تطبيق حلقتي للإنتاج" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. التحقق من Flutter
Write-Host "🔍 التحقق من Flutter..." -ForegroundColor Cyan
flutter --version
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Flutter غير مثبت" -ForegroundColor Red
    exit 1
}
Write-Host "✅ Flutter مثبت" -ForegroundColor Green
Write-Host ""

# 2. التحقق من وجود firebase_options.dart
if (-not (Test-Path "lib/firebase_options.dart")) {
    Write-Host "⚠️  ملف firebase_options.dart غير موجود" -ForegroundColor Yellow
    Write-Host "يرجى تشغيل: flutterfire configure" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "الخطوات:" -ForegroundColor Cyan
    Write-Host "1. npm install -g firebase-tools" -ForegroundColor Gray
    Write-Host "2. firebase login" -ForegroundColor Gray
    Write-Host "3. flutterfire configure" -ForegroundColor Gray
    exit 1
}
Write-Host "✅ firebase_options.dart موجود" -ForegroundColor Green

# 3. التحقق من إعدادات API
Write-Host ""
Write-Host "🔍 التحقق من إعدادات API..." -ForegroundColor Cyan
$apiConstants = Get-Content "lib/core/constants/api_constants.dart" -Raw
if ($apiConstants -match "your-domain\.com") {
    Write-Host "⚠️  رابط API لم يتم تعديله" -ForegroundColor Yellow
    Write-Host "يرجى تعديل baseUrl في api_constants.dart" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "الخيارات:" -ForegroundColor Cyan
    Write-Host "1. عدّل lib/core/constants/api_constants.dart مباشرة" -ForegroundColor Gray
    Write-Host "2. أو استخدم api_constants_production.dart" -ForegroundColor Gray
    Write-Host ""
    $response = Read-Host "هل تريد المتابعة؟ (y/n)"
    if ($response -ne "y") {
        exit 1
    }
}
Write-Host "✅ إعدادات API جاهزة" -ForegroundColor Green

# 4. الحصول على إصدار التطبيق
Write-Host ""
Write-Host "📝 إصدار التطبيق:" -ForegroundColor Cyan
$pubspec = Get-Content "pubspec.yaml" -Raw
if ($pubspec -match "version:\s+(\d+\.\d+\.\d+)\+(\d+)") {
    $version = $matches[1]
    $buildNumber = $matches[2]
    Write-Host "  Version: $version" -ForegroundColor Gray
    Write-Host "  Build: $buildNumber" -ForegroundColor Gray
}

# 5. تشغيل flutter clean
Write-Host ""
Write-Host "🧹 تنظيف الملفات القديمة..." -ForegroundColor Cyan
flutter clean
Write-Host "✅ تم التنظيف" -ForegroundColor Green

# 6. تشغيل flutter pub get
Write-Host ""
Write-Host "📦 تحميل التبعيات..." -ForegroundColor Cyan
flutter pub get
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ فشل تحميل التبعيات" -ForegroundColor Red
    exit 1
}
Write-Host "✅ تم تحميل التبعيات" -ForegroundColor Green

# 7. بناء APK
Write-Host ""
Write-Host "🔨 بناء APK للإنتاج..." -ForegroundColor Cyan
Write-Host "قد يستغرق هذا بضع دقائق..." -ForegroundColor Yellow
Write-Host ""

flutter build apk --release

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "❌ فشل بناء APK" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "✅ تم بناء APK بنجاح" -ForegroundColor Green

# 8. نسخ APK إلى مجلد مخرجات
$outputDir = "build_output"
if (Test-Path $outputDir) {
    Remove-Item $outputDir -Recurse -Force
}
New-Item -ItemType Directory -Path $outputDir | Out-Null

$apkPath = "build/app/outputs/flutter-apk/app-release.apk"
if (Test-Path $apkPath) {
    $newApkName = "halqati-v$version+$buildNumber.apk"
    Copy-Item -Path $apkPath -Destination "$outputDir/$newApkName"
    Write-Host "✅ تم نسخ APK إلى: $outputDir/$newApkName" -ForegroundColor Green
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✅ اكتمل البناء بنجاح" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "الملف: $outputDir/$newApkName" -ForegroundColor Yellow
Write-Host ""
Write-Host "الخطوات التالية:" -ForegroundColor Cyan
Write-Host "1. ارفع الملف إلى Google Drive أو Dropbox" -ForegroundColor White
Write-Host "2. شارك الرابط مع المستخدمين" -ForegroundColor White
Write-Host "3. أو ارفعه إلى Google Play Console ($25)" -ForegroundColor White
Write-Host ""
