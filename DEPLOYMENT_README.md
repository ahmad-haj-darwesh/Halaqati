# دليل النشر السريع - مشروع حلقتي

> **تم إنشاء ملفات وأدوات مساعدة لتسهيل عملية النشر**

---

## 📦 الملفات المضافة

### Backend
- `backend/.env.production.example` - قالب ملف البيئة للإنتاج
- `backend/deploy.ps1` - سكريبت تحضير الملفات للنشر

### Mobile
- `mobile/lib/core/constants/api_constants_production.dart` - إعدادات API للإنتاج
- `mobile/build_apk.ps1` - سكريبت بناء APK للإنتاج

---

## 🚀 خطوات النشر السريعة

### الخطوة 1: إعداد Firebase

1. اذهب إلى https://console.firebase.google.com
2. أنشئ مشروع جديد باسم `halqati`
3. فعل Cloud Messaging
4. من Project Settings > Service accounts، أنشئ private key
5. احفظ الملف باسم `firebase-credentials.json`
6. ضعه في `backend/storage/app/`

### الخطوة 2: إعداد Backend

```bash
cd backend
```

1. إنشاء ملف `.env.production`:
```powershell
Copy-Item .env.production.example .env.production
```

2. عدّل `.env.production`:
   - استبدل `YOUR_APP_KEY_HERE` بمفتاح التطبيق (يمكنك الحصول عليه من `.env` المحلي)
   - استبدل `your-domain.com` بنطاق InfinityFree الخاص بك
   - أدخل بيانات قاعدة البيانات من InfinityFree

3. تشغيل سكريبت التحضير:
```powershell
.\deploy.ps1
```

4. سيتم إنشاء مجلد `deploy_output` يحتوي على جميع الملفات المطلوبة

### الخطوة 3: نشر Backend على InfinityFree

1. سجل في https://infinityfree.com
2. أنشئ موقع جديد واحصل على بيانات FTP
3. استخدم FileZilla لرفع محتوى `deploy_output` إلى `/htdocs/`
4. من لوحة التحكم، أنشئ قاعدة بيانات MySQL
5. استورد قاعدة البيانات عبر phpMyAdmin:
   - صدّر قاعدة البيانات المحلية:
   ```bash
   mysqldump -u root -p halqati > halqati.sql
   ```
   - استوردها في phpMyAdmin

### الخطوة 4: إعداد Mobile

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

3. إعداد Firebase:
```bash
flutterfire configure
```
- اختر مشروع Firebase الذي أنشأته
- سيتم إنشاء `firebase_options.dart` تلقائياً

4. تعديل رابط API:
   - عدّل `lib/core/constants/api_constants.dart`
   - استبدل `https://your-domain.com/api` برابط موقعك الحقيقي
   - أو استخدم `api_constants_production.dart` واستورده بدلاً من الملف الأصلي

### الخطوة 5: بناء APK

```bash
cd mobile
.\build_apk.ps1
```

سيتم إنشاء ملف APK في مجلد `build_output`

### الخطوة 6: نشر التطبيق

1. ارفع ملف APK من `build_output` إلى Google Drive أو Dropbox
2. شارك الرابط مع المستخدمين
3. أو ارفعه إلى Google Play Console ($25 one-time)

---

## 🔧 ملاحظات مهمة

### Backend
- **InfinityFree لا يدعم queue workers أو scheduled tasks**
- تأكد من `QUEUE_CONNECTION=sync` في `.env.production`
- إذا احتجت scheduled tasks، استخدم خدمة خارجية مثل EasyCron

### Mobile
- تأكد من أن `firebase_options.dart` موجود قبل البناء
- تأكد من تعديل `baseUrl` قبل البناء
- APK الناتج جاهز للتوزيع المباشر

### Firebase
- ملف `firebase-credentials.json` يحتوي على بيانات حساسة
- **لا ترفعه إلى Git**
- احتفظ به في مكان آمن

---

## 📋 قائمة التحقق قبل النشر

- [ ] إنشاء مشروع Firebase
- [ ] تفعيل Cloud Messaging
- [ ] الحصول على `firebase-credentials.json`
- [ ] وضعه في `backend/storage/app/`
- [ ] إنشاء حساب InfinityFree
- [ ] إنشاء موقع وقاعدة بيانات
- [ ] تعديل `.env.production`
- [ ] تشغيل `deploy.ps1`
- [ ] رفع الملفات إلى InfinityFree
- [ ] استيراد قاعدة البيانات
- [ ] تشغيل `flutterfire configure`
- [ ] تعديل `baseUrl` في Mobile
- [ ] تشغيل `build_apk.ps1`
- [ ] اختبار Backend في المتصفح
- [ ] اختبار التطبيق على هاتف

---

## 🆘 حل المشاكل

### خطأ 500 في Backend
- تأكد من صحة ملف `.env.production`
- تحقق من صلاحيات المجلدات
- راجع سجلات الأخطاء في InfinityFree

### لا تعمل الإشعارات
- تأكد من `firebase-credentials.json` في المكان الصحيح
- تحقق من تفعيل Cloud Messaging في Firebase Console
- تأكد من `APP_ENV=production` في `.env.production`

### التطبيق لا يتصل بالـ API
- تأكد من `baseUrl` صحيح
- تحقق من أن API يعمل في المتصفح
- تأكد من HTTPS

---

## 📚 روابط مفيدة

- [دليل النشر الشامل](FREE_DEPLOYMENT_GUIDE.md)
- [InfinityFree](https://infinityfree.com)
- [Firebase Console](https://console.firebase.google.com)
- [Flutter Deployment](https://docs.flutter.dev/deployment)

---

**آخر تحديث:** أبريل 2026
