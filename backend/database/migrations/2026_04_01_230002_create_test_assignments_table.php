<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('halaqah_id')->constrained('halaqahs')->cascadeOnDelete();

            $table->dateTime('assigned_at')->nullable();
            $table->foreignId('assigned_by_user_id')->constrained('users')->cascadeOnDelete();

            $table->enum('status', ['assigned', 'completed', 'absent_excused', 'absent_unexcused'])->default('assigned');

            $table->timestamps();

            $table->unique(['test_id', 'student_id']);
            $table->index(['test_id', 'status']);
            $table->index(['halaqah_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_assignments');
    }
};

