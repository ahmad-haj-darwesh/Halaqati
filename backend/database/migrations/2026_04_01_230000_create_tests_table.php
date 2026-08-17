<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();

            $table->enum('type', ['regular', 'sampling']);
            $table->string('title');
            $table->text('description')->nullable();

            $table->foreignId('scope_halaqah_id')->nullable()->constrained('halaqahs')->nullOnDelete();
            $table->foreignId('scope_center_id')->nullable()->constrained('centers')->nullOnDelete();
            $table->foreignId('scope_region_id')->nullable()->constrained('regions')->nullOnDelete();

            $table->dateTime('scheduled_at')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_published')->default(false);

            // Sampling config (optional)
            $table->string('sampling_strategy', 30)->nullable(); // random|stratified
            $table->integer('sampling_count')->nullable();
            $table->decimal('sampling_percent', 5, 2)->nullable();
            $table->integer('sampling_seed')->nullable();
            $table->boolean('sampling_active_only')->default(true);

            $table->timestamps();

            $table->index(['type', 'scheduled_at']);
            $table->index(['scope_center_id', 'scope_halaqah_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};

