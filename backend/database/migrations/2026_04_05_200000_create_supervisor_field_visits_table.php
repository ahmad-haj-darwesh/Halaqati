<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_field_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('teacher_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
            $table->date('visit_date');
            $table->unsignedTinyInteger('teaching_skill_score');
            $table->unsignedTinyInteger('plan_adherence_score');
            $table->unsignedTinyInteger('student_engagement_score');
            $table->text('notes')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('status', 32)->default('completed');
            $table->timestamps();

            $table->index(['supervisor_user_id', 'visit_date']);
            $table->index(['teacher_user_id', 'visit_date']);
            $table->index(['center_id', 'visit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_field_visits');
    }
};
