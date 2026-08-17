<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('halaqahs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('capacity')->default(20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('halaqahs');
    }
};
