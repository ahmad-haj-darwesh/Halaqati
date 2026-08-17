# دليل نشر مشروع حلقتي مجاناً على الإنترنت

> **تاريخ التحديث:** أبريل 2026
> **الهدف:** نشر المشروع بالكامل بدون تكاليف

---

## 📋 ملخص الخطة

| المكون | الخيار المجاني | التكلفة |
|--------|---------------|---------|
| **Backend (Laravel)** | InfinityFree | مجاني |
| **Database (MySQL)** | InfinityFree MySQL | مجاني |
| **Firebase (FCM)** | Firebase Cloud Messaging | مجاني |
| **Mobile (Flutter)** | APK مباشر | مجاني |

---

## ⚠️ تحذيرات مهمة

1. **InfinityFree** استضافة مجانية لكنها محدودة:
   - مساحة تخزين صغيرة
   - قيود على الاستهلاك
   - قد تكون بطيئة مع الاستخدام المكثف
   - مناسبة للتجربة والمشاريع الصغيرة

2. **للمشاريع التجارية:** يُنصح باستضافة مدفوعة مثل:
   - Laravel Forge + DigitalOcean ($5/شهر)
   - Railway ($5/شهر بعد الـ trial)

---

## 🚀 الخطوة 1: إعداد Firebase للإشعارات

### 1.1 إنشاء مشروع Firebase

1. اذهب إلى https://console.firebase.google.com
2. سجل الدخول بحساب Google
3. انقر على "Add project"
4. سمِّ المشروع: `halqati`
5. اتبع الخطوات وفعّل Google Analytics (اختياري)

### 1.2 تفعيل Cloud Messaging

1. من Firebase Console، اختر مشروعك
2. من القائمة الجانبية، اختر **Cloud Messaging**
3. تفعّل **Cloud Messaging API (V1)**
4. احفظ الإعدادات

### 1.3 الحصول على ملف الاعتماد للـ Backend

1. من Firebase Console، اختر **Project Settings** (الإعدادات)
2. من تبويب **Service accounts**، انقر **Generate new private key**
3. احفظ الملف باسم `firebase-credentials.json`
4. **⚠️ لا ترفع هذا الملف إلى Git**

### 1.4 إعداد Firebase في Backend

```bash
cd backend
```

1. ضع `firebase-credentials.json` في:
   ```
   backend/storage/app/firebase-credentials.json
   ```

2. عدّل `.env`:
   ```env
   FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json
   ```

3. تأكد من أن `APP_ENV=local` حالياً (سنتغيره لاحقاً)

---

## 🌐 الخطوة 2: إعداد استضافة InfinityFree

### 2.1 إنشاء حساب InfinityFree

1. اذهب إلى https://infinityfree.com
2. انقر **Register**
3. املأ البيانات المطلوبة
4. سجل الدخول

### 2.2 إنشاء موقع جديد

1. من لوحة التحكم، انقر **Create Account**
2. اختر اسم فرعي (subdomain) مثل: `halqati.rf.gd`
3. اختر كلمة مرور
4. انقر **Create Account**

### 2.3 إنشاء قاعدة بيانات MySQL

1. من لوحة التحكم، اختر موقعك
2. من قائمة **MySQL Databases**، انقر **Create Database**
3. سجل المعلومات:
   - **Database Name:** احفظها
   - **Username:** احفظه
   - **Password:** احفظه
   - **Hostname:** عادة `sqlxxx.infinityfree.com`

### 2.4 رفع ملفات Laravel

**⚠️ ملاحظة مهمة:** InfinityFree لا يدعم Composer أو Artisan مباشرة. يجب رفع الملفات المبنية.

#### الخيار أ: استخدام FTP (موصى به)

1. من لوحة التحكم، احصل على بيانات FTP:
   - **FTP Host:** `ftpupload.net`
   - **FTP Username:** من لوحة التحكم
   - **FTP Password:** من لوحة التحكم

2. استخدم برنامج FTP مثل FileZilla:
   - قم بتثبيت FileZilla
   - أدخل بيانات FTP
   - اتصل بالخادم

3. ارفع محتوى مجلد `backend/public` إلى:
   ```
   /htdocs/
   ```

#### الخيار ب: استخدام File Manager

1. من لوحة التحكم، اختر **Online File Manager**
2. انتقل إلى `/htdocs/`
3. احذف الملفات الموجودة (index.html إلخ)
4. ارفع ملفات `backend/public`

### 2.5 إعداد قاعدة البيانات

1. من لوحة التحكم، اختر **phpMyAdmin**
2. اختر قاعدة البيانات التي أنشأتها
3. انقر **Import**
4. ارفع ملف SQL من جهازك:
   ```bash
   cd backend
   php artisan migrate --seed
   mysqldump -u root -p halqati > halqati.sql
   ```
   أو استخدم ملف التصدير من قاعدة البيانات المحلية

### 2.6 تعديل ملف .env

في InfinityFree، أنشئ ملف `.env` في `/htdocs/`:

```env
APP_NAME="حلقتي"
APP_ENV=production
APP_KEY=your-generated-key
APP_DEBUG=false
APP_URL=https://halqati.rf.gd

APP_LOCALE=ar
APP_FALLBACK_LOCALE=ar

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=sqlxxx.infinityfree.com  # من لوحة التحكم
DB_PORT=3306
DB_DATABASE=your_db_name         # من لوحة التحكم
DB_USERNAME=your_db_username     # من لوحة التحكم
DB_PASSWORD=your_db_password     # من لوحة التحكم

SESSION_DRIVER=file
SESSION_LIFETIME=120

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=file

SANCTUM_STATEFUL_DOMAINS=halqati.rf.gd

FIREBASE_CREDENTIALS=firebase-credentials.json

MAIL_MAILER=log
```

**⚠️ ملاحظة:** InfinityFree لا يدعم queue workers أو scheduled tasks. يجب تعطيلها:
```env
QUEUE_CONNECTION=sync
```

---

## 📱 الخطوة 3: إعداد Flutter Mobile

### 3.1 إعداد Firebase في Flutter

```bash
cd mobile
```

1. تثبيت Firebase CLI:
   ```bash
   npm install -g firebase-tools
   ```

2. تسجيل الدخول:
   ```bash
   firebase login
   ```

3. إعداد المشروع:
   ```bash
   flutterfire configure
   ```
   - اختر مشروع Firebase الذي أنشأته
   - سيقوم بإنشاء `firebase_options.dart`
   - سيقوم بتحميل ملفات الاعتماد

### 3.2 تعديل رابط API

عدّل `mobile/lib/core/constants/api_constants.dart`:

```dart
static String get baseUrl {
  // استبدل برابط موقعك الحقيقي
  return 'https://halqati.rf.gd/api';
}
```

### 3.3 بناء تطبيق Android

```bash
cd mobile
flutter build apk --release
```

الملف الناتج:
```
build/app/outputs/flutter-apk/app-release.apk
```

### 3.4 بناء تطبيق iOS (يتطلب Mac)

```bash
cd mobile
flutter build ios --release
```

---

## 📤 الخطوة 4: نشر التطبيق

### 4.1 نشر APK مباشر (مجاني)

1. ارفع `app-release.apk` إلى خدمة مثل:
   - **Google Drive** (مشاركة رابط)
   - **Dropbox** (مشاركة رابط)
   - **GitHub Releases**

2. شارك الرابط مع المستخدمين
3. المستخدمون يحملون ويثبتون APK مباشرة

### 4.2 نشر على Google Play (مدفوع - $25 one-time)

1. أنشئ حساب Google Play Console ($25)
2. أنشئ تطبيق جديد
3. ارفع APK أو AAB
4. أكمل المعلومات المطلوبة
5. انتظر المراجعة (1-3 أيام)

### 4.3 نشر على App Store (مدفوع - $99/year)

1. أنشئ حساب Apple Developer ($99/year)
2. استخدم Xcode لرفع التطبيق
3. أكمل المعلومات المطلوبة
4. انتظر المراجعة (1-2 أسبوع)

---

## 🔧 الخطوة 5: اختبار النشر

### 5.1 اختبار Backend

1. افتح المتصفح واذهب إلى:
   ```
   https://halqati.rf.gd
   ```

2. اختبر API:
   ```
   https://halqati.rf.gd/api/login
   ```

3. تأكد من لوحة Filament:
   ```
   https://halqati.rf.gd/admin
   ```

### 5.2 اختبار Mobile

1. ثبّت APK على هاتفك
2. سجل الدخول بحساب معلم
3. اختبر:
   - عرض الطلاب
   - السجل اليومي
   - الإشعارات (تتطلب اتصال بالإنترنت)

---

## ⚙️ الخطوة 6: تحديثات وصيانة

### 6.1 تحديث Backend

1. عدّل الكود محلياً
2. ارفع الملفات المعدلة عبر FTP
3. إذا كانت هناك تغييرات في قاعدة البيانات:
   - صدّر التغييرات محلياً
   - استوردها في phpMyAdmin

### 6.2 تحديث Mobile

1. عدّل الكود
2. ابنِ نسخة جديدة:
   ```bash
   flutter build apk --release
   ```
3. ارفع APK الجديد
4. شارك الرابط الجديد

---

## 🎯 ملخص المتطلبات

### قبل البدء:

- ✅ حساب Google (لـ Firebase)
- ✅ حساب InfinityFree
- ✅ FileZilla (للـ FTP)
- ✅ Android Studio (لبناء APK)
- ✅ متصفح Chrome

### الملفات المطلوبة:

- ✅ `firebase-credentials.json` (من Firebase)
- ✅ `firebase_options.dart` (يُنشأ تلقائياً)
- ✅ `google-services.json` (يُنشأ تلقائياً)
- ✅ `GoogleService-Info.plist` (يُنشأ تلقائياً)

---

## 🚨 المشاكل الشائعة والحلول

### مشكلة: خطأ 500 في Backend

**الحل:**
1. تأكد من صحة ملف `.env`
2. تحقق من صلاحيات المجلدات (755 للملفات، 777 للمجلدات)
3. راجع سجلات الأخطاء في InfinityFree

### مشكلة: لا تعمل الإشعارات

**الحل:**
1. تأكد من `firebase-credentials.json` في المكان الصحيح
2. تحقق من تفعيل Cloud Messaging في Firebase Console
3. تأكد من `APP_ENV=production` في `.env`

### مشكلة: التطبيق لا يتصل بالـ API

**الحل:**
1. تأكد من `baseUrl` في `api_constants.dart`
2. تحقق من أن API يعمل في المتصفح
3. تأكد من HTTPS (InfinityFree يدعم HTTPS تلقائياً)

### مشكلة: لا تعمل Scheduled Tasks

**الحل:**
- InfinityFree لا يدعم cron jobs
- يجب تعطيل الجدولة أو استخدام خدمة خارجية مثل:
  - **EasyCron** (مجاني للجدولة البسيطة)
  - **Cron-job.org** (مجاني)

---

## 📊 مقارنة مع الخيارات المدفوعة

| الميزة | InfinityFree (مجاني) | Railway ($5/شهر) | Laravel Forge ($5/شهر) |
|--------|---------------------|------------------|------------------------|
| **PHP** | ✅ | ✅ | ✅ |
| **MySQL** | ✅ | ✅ | ❌ (منفصل) |
| **Queue Workers** | ❌ | ✅ | ✅ |
| **Scheduled Tasks** | ❌ | ✅ | ✅ |
| **SSL** | ✅ | ✅ | ✅ |
| **Domain** | فرعي مجاني | مخصص | مخصص |
| **السرعة** | بطيء | سريع | سريع |
| **الدعم** | محدود | جيد | ممتاز |

---

## 🎓 التوصية النهائية

**للتجربة والتعلم:**
- استخدم **InfinityFree** (مجاني بالكامل)
- مناسب للمشاريع الصغيرة والتجريبية

**للمشاريع التجارية:**
- استخدم **Railway** أو **Laravel Forge**
- تكلفة: $5/شهر
- أداء أفضل وميزات أكثر

---

## 📚 روابط مفيدة

- [InfinityFree](https://infinityfree.com)
- [Firebase Console](https://console.firebase.google.com)
- [Flutter Deployment](https://docs.flutter.dev/deployment)
- [FileZilla Download](https://filezilla-project.org)

---

**آخر تحديث:** أبريل 2026
