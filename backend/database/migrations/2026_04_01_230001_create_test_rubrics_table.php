<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_rubrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('max_score')->default(0);
            $table->decimal('weight', 6, 3)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['test_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_rubrics');
    }
};

