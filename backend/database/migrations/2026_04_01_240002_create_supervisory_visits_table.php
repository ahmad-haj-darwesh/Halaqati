<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisory_visits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supervision_rubric_id')->constrained('supervision_rubrics')->restrictOnDelete();
            $table->foreignId('supervisor_user_id')->constrained('users')->cascadeOnDelete();

            $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
            $table->foreignId('halaqah_id')->constrained('halaqahs')->cascadeOnDelete();
            $table->foreignId('teacher_user_id')->constrained('users')->cascadeOnDelete();

            $table->dateTime('visited_at');
            $table->integer('duration_minutes')->nullable();

            $table->enum('overall_level', ['excellent', 'good', 'acceptable', 'weak'])->nullable();
            $table->decimal('overall_score', 7, 2)->nullable();

            $table->text('summary')->nullable();
            $table->text('recommendations')->nullable();

            $table->boolean('is_finalized')->default(false);
            $table->timestamps();

            $table->index(['supervisor_user_id', 'visited_at']);
            $table->index(['center_id', 'visited_at']);
            $table->index(['teacher_user_id', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisory_visits');
    }
};
