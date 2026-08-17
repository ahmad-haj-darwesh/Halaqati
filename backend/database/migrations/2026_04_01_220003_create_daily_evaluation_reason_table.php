<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_evaluation_reason', function (Blueprint $table) {
            $table->foreignId('daily_evaluation_id')->constrained('daily_evaluations')->cascadeOnDelete();
            $table->foreignId('evaluation_reason_id')->constrained('evaluation_reasons')->cascadeOnDelete();

            // MySQL: اسم القيد الافتراضي يتجاوز 64 حرفًا؛ نحدد اسمًا قصيرًا صراحةً
            $table->unique(['daily_evaluation_id', 'evaluation_reason_id'], 'de_eval_reason_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_evaluation_reason');
    }
};

