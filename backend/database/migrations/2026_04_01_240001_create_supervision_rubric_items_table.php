<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervision_rubric_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervision_rubric_id')->constrained('supervision_rubrics')->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->integer('max_score')->default(5);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['supervision_rubric_id', 'key']);
            $table->index(['supervision_rubric_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervision_rubric_items');
    }
};

