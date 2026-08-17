# دليل تثبيت بيئة تطوير مشروع حلقتي

> هذا الدليل مخصّص لجهاز جديد لم يُثبَّت عليه أي شيء مسبقًا.
> جميع الأدوات مطلوبة لتشغيل الجزء الخلفي (Backend) والتطبيق المحمول (Mobile).

---

## 1. Git — نظام التحكم بالإصدارات

- **رابط التحميل:** https://git-scm.com/download/win
- **الغرض:** إدارة النسخ ومزامنة الكود مع المستودع البعيد.
- **بعد التثبيت:** تأكد من أن الأمر `git` يعمل من سطر الأوامر.

---

## 2. PHP 8.2+ — لغة تشغيل الخلفية

- **طريقة التثبيت الموصى بها:** تثبيت **XAMPP** (يتضمن Apache + MySQL + PHP) من الرابط:
  https://www.apachefriends.org/
- **بدائل:** يمكنك تثبيت PHP منفصلاً وإضافته إلى متغير `PATH` في النظام.
- **إصدار مطلوب:** `^8.2` حسب ملف `composer.json`
- **امتدادات PHP المطلوبة** (تأتي افتراضيًا مع XAMPP، لكن تحقق منها):
  - `pdo_mysql` — للاتصال بقاعدة بيانات MySQL
  - `mbstring` — لدعم النصوص متعددة البايتات (العربية)
  - `xml` — لمعالجة XML
  - `curl` — لطلبات HTTP
  - `zip` — لضغط الملفات
  - `gd` — لمعالجة الصور
  - `intl` — للدولي (أسماء اللغات والمناطق)
  - `bcmath` — للعمليات الحسابية الدقيقة
  - `fileinfo` — لاكتشاف أنواع الملفات

---

## 3. Composer — مدير حزم PHP

- **رابط التحميل:** https://getcomposer.org/download/
- **الغرض:** تثبيت جميع مكتبات Laravel المعرفة في `composer.json` مثل:
  - `filament/filament` — لوحة الإدارة
  - `laravel/sanctum` — المصادقة عبر API
  - `maatwebsite/excel` — تصدير Excel
  - `spatie/laravel-permission` — إدارة الصلاحيات
- **بعد التثبيت:** تحقق بالأمر `composer --version`

---

## 4. MySQL 8+ — قاعدة البيانات

- **مضمن مع XAMPP** — لا حاجة لتثبيت منفصل إذا استخدمت XAMPP.
- **تثبيت منفصل:** https://dev.mysql.com/downloads/installer/
- **الإصدار المطلوب:** MySQL 8.0 أو أحدث (أو MariaDB كبديل)
- **بعد التثبيت:**
  1. شغّل خادم MySQL
  2. أنشئ قاعدة بيانات باسم `halqati`:
     ```sql
     CREATE DATABASE halqati CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     ```
  3. حدّث بيانات الاتصال في ملف `.env`:
     ```
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=halqati
     DB_USERNAME=root
     DB_PASSWORD=
     ```

---

## 5. Node.js 18+ (LTS) و npm — لبناء الأصول الأمامية

- **رابط التحميل:** https://nodejs.org/
- **الغرض:**
  - **Vite** — حزم الأصول الأمامية بسرعة عالية
  - **Tailwind CSS** — إطار تنسيق CSS
  - **concurrently** — تشغيل عدة خوادم تطوير معًا
- **ملاحظة:** يأتي `npm` مدمجًا مع Node.js، لا حاجة لتثبيت منفصل.
- **بعد التثبيت:** تحقق بالأمر `node --version` و `npm --version`

---

## 6. Flutter SDK — إطار تطوير التطبيق المحمول

- **رابط التحميل:** https://docs.flutter.dev/get-started/install/windows/desktop
- **الإصدار المطلوب:** Dart SDK `^3.9.2` حسب ملف `pubspec.yaml`
- **بعد التثبيت:**
  1. أضف مسار Flutter إلى متغير `PATH`
  2. شغّل الأمر التالي للتحقق من جاهزية كل شيء:
     ```
     flutter doctor
     ```
  3. يجب أن تظهر علامة ✓ بجانب Flutter و Android toolchain

---

## 7. Android Studio — لتشغيل التطبيق على أندرويد

- **رابط التحميل:** https://developer.android.com/studio
- **الغرض:**
  - **Android SDK** — أدوات بناء تطبيقات أندرويد
  - **Android Emulator** — محاكي لتشغيل التطبيق على الكمبيوتر
  - **Android Build Tools** — أدوات البناء
- **بعد التثبيت:**
  1. افتح Android Studio → Settings → SDK Manager
  2. ثبّت **Android SDK** (مستوى API متوافق مع المشروع)
  3. أنشئ **Android Virtual Device (AVD)** من Device Manager
  4. اقبل جميع تراخيص SDK:
     ```
     flutter doctor --android-licenses
     ```

---

## 8. JDK 11 — بيئة تطوير جافا (لبناء أندرويد)

- **رابط التحميل:** https://adoptium.net/
- **الغرض:** بناء تطبيق أندرويد (المشروع يستخدم `JavaVersion.VERSION_11`)
- **بعد التثبيت:**
  1. عيّن متغير البيئة `JAVA_HOME` إلى مسار تثبيت JDK
  2. أضف `%JAVA_HOME%\bin` إلى متغير `PATH`

---

## 9. محرر أكواد — VS Code (اختياري لكن موصى به)

- **رابط التحميل:** https://code.visualstudio.com/
- **الإضافات الموصى بها:**
  - **PHP Intelephense** — الذكاء البرمجي لـ PHP
  - **Laravel Blade** — دعم قوالب Blade
  - **Flutter** — دعم تطوير Flutter
  - **Dart** — دعم لغة Dart
  - **MySQL** — إدارة قواعد البيانات

---

## 10. Firebase — للإشعارات الفورية

- **ليس برنامجًا** بل خدمة سحابية يجب إعدادها.
- **المطلوب:**
  1. إنشاء مشروع Firebase من: https://console.firebase.google.com/
  2. تفعيل **Cloud Messaging** للمشروع
  3. تحميل ملف بيانات الخدمة (`firebase-credentials.json`)
  4. وضع الملف في مسار آمن على الخادم
  5. تعيين المسار في ملف `.env`:
     ```
     FIREBASE_CREDENTIALS=/path/to/firebase-credentials.json
     ```

---

## ملخص الأدوات المطلوبة

| # | الأداة | الإصدار | الغرض |
|---|--------|---------|-------|
| 1 | Git | أحدث | التحكم بالإصدارات |
| 2 | PHP | 8.2+ | تشغيل الخلفية (Laravel) |
| 3 | Composer | 2.x | إدارة حزم PHP |
| 4 | MySQL | 8.0+ | قاعدة البيانات |
| 5 | Node.js | 18+ LTS | بناء الأصول الأمامية (Vite) |
| 6 | Flutter SDK | 3.x (Dart ^3.9.2) | تطوير التطبيق المحمول |
| 7 | Android Studio | أحدث | Android SDK + المحاكي |
| 8 | JDK | 11 | بناء تطبيق أندرويد |
| 9 | VS Code | أحدث | محرر الأكواد |
| 10 | Firebase Credentials | — | الإشعارات الفورية |

---

## أوامر الإعداد بعد تثبيت كل شيء

### إعداد الخلفية (Backend)

```bash
# الانتقال إلى مجلد الخلفية
cd backend

# نسخ ملف البيئة
copy .env.example .env

# توليد مفتاح التطبيق
php artisan key:generate

# تحديث بيانات قاعدة البيانات في .env ثم تنفيذ الهجرات
php artisan migrate:fresh --seed

# تثبيت حزم PHP
composer install

# تثبيت حزم Node.js
npm install

# تشغيل بيئة التطوير (الخادم + الطابور + السجلات + Vite)
composer dev
```

### إعداد التطبيق المحمول (Mobile)

```bash
# الانتقال إلى مجلد التطبيق
cd mobile

# تثبيت حزم Dart/Flutter
flutter pub get

# تشغيل التطبيق على المحاكي
flutter run
```

### تشغيل الاختبارات

```bash
# اختبارات الخلفية
cd backend
php artisan test

# اختبارات التطبيق المحمول
cd mobile
flutter test
```

---

## ملاحظات مهمة

- **XAMPP يوفّر عليك:** إذا ثبّبت XAMPP، ستحصل على PHP + MySQL + Apache معًا، فلا حاجة لتثبيتها منفصلة.
- **مؤقت الطابور (Queue Worker):** المشروع يستخدم `QUEUE_CONNECTION=database`، لذا يجب تشغيل `php artisan queue:listen` أو استخدام أمر `composer dev` الذي يشغّل كل شيء معًا.
- **الجلسات والتخزين المؤقت:** يستخدم المشروع `SESSION_DRIVER=database` و `CACHE_STORE=database`، لذا تأكد من تنفيذ الهجرات قبل الاستخدام.
- **عنوان المحاكي:** عند تشغيل التطبيق على محاكي أندرويد، استخدم `10.0.2.2` بدلاً من `localhost` للوصول إلى الخادم المحلي.
