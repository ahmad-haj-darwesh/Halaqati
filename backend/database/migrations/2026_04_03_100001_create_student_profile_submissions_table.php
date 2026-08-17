<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profile_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('status', 20)->default('pending')->index();

            $table->string('full_name');
            $table->string('gender', 10);
            $table->date('birth_date')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('national_id')->nullable();
            $table->text('notes')->nullable();
            $table->string('photo_path')->nullable();

            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_note')->nullable();

            $table->timestamps();

            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profile_submissions');
    }
};
