<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisory_visit_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supervisory_visit_id')->constrained('supervisory_visits')->cascadeOnDelete();
            $table->foreignId('supervision_rubric_item_id')->constrained('supervision_rubric_items')->cascadeOnDelete();
            $table->decimal('score', 6, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['supervisory_visit_id', 'supervision_rubric_item_id'], 'sv_score_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisory_visit_scores');
    }
};

