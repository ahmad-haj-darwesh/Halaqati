<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->string('full_name');
            $table->string('gender', 10); // male|female (validated at app level)
            $table->date('birth_date')->nullable();

            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone')->nullable();

            $table->string('national_id')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['full_name', 'guardian_phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};

