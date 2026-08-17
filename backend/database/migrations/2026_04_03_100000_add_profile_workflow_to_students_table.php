<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('notes');
            $table->boolean('profile_locked')->default(false);
            $table->boolean('teacher_may_edit_profile')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'profile_locked', 'teacher_may_edit_profile']);
        });
    }
};
