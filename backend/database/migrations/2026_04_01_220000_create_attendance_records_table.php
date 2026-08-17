<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('halaqah_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status', 30); // present|excused_absence|unexcused_absence

            $table->foreignId('recorded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'date']);
            $table->index(['halaqah_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};

