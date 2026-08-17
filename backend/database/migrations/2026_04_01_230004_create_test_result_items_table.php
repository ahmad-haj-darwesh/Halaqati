<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_result_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('test_result_id')->constrained('test_results')->cascadeOnDelete();
            $table->foreignId('test_rubric_id')->constrained('test_rubrics')->cascadeOnDelete();

            $table->decimal('score', 8, 2)->default(0);
            $table->text('notes')->nullable();

            $table->unique(['test_result_id', 'test_rubric_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_result_items');
    }
};

