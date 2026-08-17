# حلقتي — نظام إدارة الحلقات القرآنية

مشروع تخرج لإدارة الحلقات القرآنية يتكون من:
- **Laravel 12** — Backend API + Dashboard (Filament 3)
- **Flutter** — تطبيق موبايل للمعلمين
- **MySQL** — قاعدة البيانات

---

## هيكل المشروع

```
halqati/
├── backend/        ← Laravel (API + Filament Dashboard)
└── mobile/         ← Flutter (Teacher App)
```

---

## المرحلة 0 و 1 — ما تم تنفيذه

### الأدوار
| الدور | الصلاحيات |
|-------|-----------|
| **SuperAdmin** | قراءة كل شيء، تعديل/حذف Admins فقط |
| **Admin** | CRUD ضمن مراكزه/حلقاته/معلميه |
| **EducationalSupervisor** | قراءة المراكز/الحلقات/المعلمين |
| **CenterSupervisor** | قراءة ضمن مراكزه |
| **Examiner** | قراءة الحلقات والمعلمين |
| **Teacher** | يستخدم Flutter API فقط |

### النماذج والعلاقات
```
Region → hasMany Centers
Center → hasMany Halaqahs (+ admin_user_id FK)
Halaqah → hasOne TeacherProfile
TeacherProfile → belongsTo User
```

---

## المرحلة 2 — الطلاب والتسجيلات (Enrollments)

### الجداول
- **students**: بيانات الطالب + `is_active` + فهرس على (`full_name`, `guardian_phone`)
- **enrollments**: ربط الطالب بالحلقة + `status` + تواريخ (enrolled/left)

### العلاقات
```
Student → hasMany Enrollments
Student → currentEnrollment() = آخر Enrollment نشط
Halaqah → hasMany Enrollments
Halaqah → students() عبر enrollments
```

### قيود مهمة
- لا يمكن للطالب امتلاك **أكثر من Enrollment نشط** (مُطبّق على مستوى التطبيق لتوافق SQLite بالاختبارات).

---

## المرحلة 3 — شاشة المعلم اليومية (حضور/تقييم/حفظ)

### Backend (API)
- جداول: `attendance_records`, `daily_evaluations`, `evaluation_reasons`, `daily_evaluation_reason`, `memorization_entries`
- Endpoints للمعلم:
  - `GET /api/teacher/halaqah/today`
  - `POST /api/teacher/daily-records/upsert`
  - `GET /api/teacher/reports/monthly?month=YYYY-MM`

### Mobile (Flutter)
- شاشة **تسجيل اليوم**: حضور + تقييم + تفاصيل (أسباب/ملاحظات/أخطاء/مكان الحفظ والمراجعة) + زر **حفظ الكل**
- شاشة **تقرير الشهر**: KPIs + أكثر الأسباب تكراراً

---

## المرحلة 4 — الاختبارات (Examiner) + عينات (Sampling)

### Backend (Laravel + Filament)
- جداول: `tests`, `test_rubrics`, `test_assignments`, `test_results`, `test_result_items`
- توليد عينات: `app/Services/SamplingAssignmentService.php`
  - يدعم `random` و `stratified`
  - يدعم `sampling_seed` ليكون الاختيار deterministic
- Filament:
  - `TestResource`: إنشاء اختبار + Generate Sample Assignments + نشر الاختبار + عرض التعيينات
  - `TestAssignmentResource`: متابعة التعيينات + تعديل الحالة
  - `TestResultResource`: إدخال النتائج

---

## المرحلة 5 — الزيارات الإشرافية (EducationalSupervisor)

### Backend (Laravel + Filament)
- جداول:
  - `supervision_rubrics`
  - `supervision_rubric_items`
  - `supervisory_visits`
  - `supervisory_visit_scores`
  - `supervisory_visit_attachments` (اختياري)
- Filament:
  - `SupervisionRubricResource`: قوالب ومحاور (Admin/SuperAdmin)
  - `SupervisoryVisitResource`: إنشاء زيارة + Finalize + Duplicate + إدخال درجات المحاور
  - Widgets: توزيع المستويات + متوسط الدرجات حسب المعلم

### Seed (بيانات تجريبية)
ينشئ قالب افتراضي ومحاور + مستخدم `supervisor@halqati.local` (EducationalSupervisor) + زيارة تجريبية.

---

## المرحلة 6 — التقارير ولوحة مؤشرات KPI (Filament)

### الهدف
- لوحة مؤشرات (KPI) مع فلاتر نطاق/فترة.
- صفحات تقارير تفصيلية للحضور/الاختبارات/الزيارات.
- احترام **نطاق الصلاحيات**: SuperAdmin يرى الكل، وباقي الأدوار محصورة في `managedCenters`.
- تصدير **CSV** من صفحات التقارير.

### أين تجدها في Filament؟
- **لوحة المؤشرات**: صفحة `لوحة المؤشرات` (Dashboard)
  - Filters: نطاق (الكل/منطقة/مركز/حلقة) + فترة (من/إلى)
  - Widgets: KPIs + حضور يوميًا + توزيع مستويات الاختبارات + أكثر أسباب التميّز/التقصير
- **التقارير** (Navigation Group: `التقارير`)
  - `تقارير الحضور` + زر `تصدير CSV`
  - `تقارير الاختبارات` + زر `تصدير CSV`
  - `تقارير الزيارات` + زر `تصدير CSV`

### ملاحظات الأداء
- تم إضافة فهارس (Indexes) داعمة للتجميعات في Migration: `2026_04_01_250000_add_reporting_indexes.php`.
- التصدير CSV محدود افتراضيًا إلى 5000 صف لتفادي الضغط.

---

## إعداد Laravel (Backend)

### 1. متطلبات
- PHP 8.3+
- Composer
- MySQL 8+ (أو MariaDB)

### 2. إنشاء قاعدة البيانات
أنشئ قاعدة `halqati` من خلال phpMyAdmin أو سطر الأوامر (بحسب بيئتك).

### 3. ضبط البيئة
```bash
cd backend
cp .env.example .env
# عدّل .env:
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=halqati
# DB_USERNAME=root
# DB_PASSWORD=
```

### 4. تثبيت وتشغيل
```bash
cd backend
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

### بيانات SuperAdmin الافتراضية
| الحقل | القيمة |
|-------|--------|
| Email | `superadmin@halqati.local` |
| Password | `password` |

### 5. الوصول للداشبورد
افتح: http://localhost:8000/admin

---

## تشغيل اختبارات Laravel

> الاختبارات تستخدم SQLite في الذاكرة (لا تحتاج MySQL)

```bash
cd backend
php artisan test
```

**النتائج المتوقعة:** 44 اختبار ✅

| الملف | الاختبارات |
|-------|-----------|
| `AuthTest` | تسجيل دخول المعلم، رفض غير المعلم، التوكن |
| `SuperAdminPolicyTest` | SuperAdmin يعدّل Admin فقط |
| `AdminScopePolicyTest` | Admin محصور في نطاقه |
| `RelationshipsTest` | سلامة العلاقات والقيود |

---

## إعداد Flutter (Mobile)

### 1. متطلبات
- Flutter 3.x+
- Android Studio / Xcode

### 2. تثبيت وتشغيل
```bash
cd mobile
flutter pub get
flutter run
```

> للتشغيل على المحاكي Android: الخادم على `10.0.2.2:8000`
> للتشغيل على جهاز حقيقي: غيّر `baseUrl` في `lib/core/constants/api_constants.dart`

### 3. بنية المشروع Flutter
```
lib/
├── core/
│   ├── constants/api_constants.dart   ← رابط API والثوابت
│   └── errors/api_exception.dart      ← نموذج الخطأ
├── features/
│   ├── auth/
│   │   ├── data/auth_repository.dart  ← Login/Logout منطق
│   │   └── presentation/login_page.dart
│   └── home/
│       └── presentation/home_page.dart
├── services/api/api_client.dart       ← Dio client + Auth interceptor
├── storage/token_storage.dart         ← FlutterSecureStorage wrapper
└── main.dart
```

---

## تشغيل اختبارات Flutter

```bash
cd mobile
dart run build_runner build --delete-conflicting-outputs
flutter test
```

**النتائج المتوقعة:** 15 اختبار ✅

| الملف | الاختبارات |
|-------|-----------|
| `token_storage_test.dart` | حفظ/قراءة/حذف التوكن (mock) |
| `api_client_test.dart` | ApiException، JSON parsing، TokenStorage mock |

---

## API Endpoints

| Method | Endpoint | Auth | الوصف |
|--------|----------|------|-------|
| POST | `/api/login` | — | تسجيل دخول المعلم |
| POST | `/api/logout` | Bearer | تسجيل الخروج |
| GET | `/api/teacher/me` | Bearer (Teacher) | بيانات المعلم |
| GET | `/api/teacher/students` | Bearer (Teacher) | طلاب حلقة المعلم |
| GET | `/api/teacher/students/{student}` | Bearer (Teacher) | تفاصيل طالب ضمن حلقة المعلم |
| GET | `/api/teacher/halaqah/today` | Bearer (Teacher) | طلاب الحلقة + سجلات اليوم + الأسباب |
| POST | `/api/teacher/daily-records/upsert` | Bearer (Teacher) | upsert سجلات اليوم (batch) |
| GET | `/api/teacher/reports/monthly?month=YYYY-MM` | Bearer (Teacher) | تقرير شهري للمعلم |

---

## ملاحظات تقنية

- **Auth Dashboard**: Filament session-based (لا يحتاج Sanctum)
- **Auth API**: Laravel Sanctum Bearer Token (للمعلم فقط)
- **Scoping**: البيانات تُستنتج من `halaqah → center → region` (لا foreign key مباشر)
- **SuperAdmin restriction**: Policy تمنع تعديل أي دور غير Admin
- **SQLite for testing**: phpunit.xml يضبط `:memory:` تلقائياً
