<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('halaqah_id')->constrained()->cascadeOnDelete();

            $table->date('enrolled_at')->default(DB::raw('CURRENT_DATE'));
            $table->string('status', 20)->default('active'); // active|paused|graduated|dropped
            $table->date('left_at')->nullable();
            $table->string('leave_reason')->nullable();

            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['halaqah_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};

