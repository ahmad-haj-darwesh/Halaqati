<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_evaluations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('halaqah_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('date');

            $table->string('overall', 30)->default('none'); // excellent|good|needs_improvement|none
            $table->foreignId('recorded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('general_note')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'date']);
            $table->index(['halaqah_id', 'date']);
            $table->index(['overall', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_evaluations');
    }
};

