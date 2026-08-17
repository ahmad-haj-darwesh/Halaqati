<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memorization_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('halaqah_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('date');

            $table->string('memorization_from')->nullable();
            $table->string('memorization_to')->nullable();
            $table->string('revision_from')->nullable();
            $table->string('revision_to')->nullable();
            $table->text('mistakes')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'date']);
            $table->index(['halaqah_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memorization_entries');
    }
};

