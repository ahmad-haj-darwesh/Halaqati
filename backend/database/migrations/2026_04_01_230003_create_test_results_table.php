<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('test_assignment_id')->unique()->constrained('test_assignments')->cascadeOnDelete();
            $table->foreignId('examiner_user_id')->constrained('users')->cascadeOnDelete();

            $table->decimal('total_score', 6, 2)->nullable();
            $table->enum('level', ['excellent', 'good', 'acceptable', 'weak'])->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('tested_at')->nullable();

            $table->timestamps();

            $table->index(['examiner_user_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_results');
    }
};

