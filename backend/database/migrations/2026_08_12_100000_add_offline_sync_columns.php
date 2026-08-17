<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * أعمدة دعم العمل دون إنترنت (Offline-first) والمزامنة المؤجلة.
 *
 * Arabic: يضيف لكل جدول يكتب فيه تطبيق الجوال عمودين للتدقيق:
 *  - `client_recorded_at`: لحظة إدخال المستخدم للبيانات على الجهاز (قد تسبق المزامنة بأيام).
 *  - `synced_at`: لحظة وصول البيانات فعلياً إلى الخادم.
 * ويضيف `client_uuid` للزيارات الميدانية لأنها نقطة الكتابة الوحيدة غير الـ idempotent
 * (تستخدم `create()` لا `updateOrCreate()`)، فبدون مفتاح فريد تُنشئ إعادةُ المحاولة زيارةً مكررة.
 * EN: Adds offline-sync audit columns plus an idempotency key for field visits.
 */
return new class extends Migration
{
    /**
     * الجداول التي يكفيها عمودا التدقيق (كلها idempotent أصلاً عبر updateOrCreate).
     *
     * @var list<string>
     */
    private const AUDIT_ONLY_TABLES = [
        'attendance_records',
        'daily_evaluations',
        'memorization_entries',
        'test_results',
    ];

    public function up(): void
    {
        foreach (self::AUDIT_ONLY_TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->timestamp('client_recorded_at')->nullable()->after('id');
                $t->timestamp('synced_at')->nullable()->after('client_recorded_at');
            });
        }

        Schema::table('supervisor_field_visits', function (Blueprint $t) {
            // فريد لكن nullable: الصفوف القديمة والزيارات المنشأة من الويب تبقى NULL،
            // وقيمتا NULL لا تتعارضان في فهرس فريد على MySQL ولا SQLite.
            $t->uuid('client_uuid')->nullable()->unique()->after('id');
            $t->timestamp('client_recorded_at')->nullable()->after('client_uuid');
            $t->timestamp('synced_at')->nullable()->after('client_recorded_at');
        });
    }

    public function down(): void
    {
        foreach (self::AUDIT_ONLY_TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['client_recorded_at', 'synced_at']);
            });
        }

        Schema::table('supervisor_field_visits', function (Blueprint $t) {
            $t->dropUnique(['client_uuid']);
            $t->dropColumn(['client_uuid', 'client_recorded_at', 'synced_at']);
        });
    }
};
