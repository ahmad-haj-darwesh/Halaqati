<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_results', function (Blueprint $table) {
            $table->unsignedSmallInteger('memorization_score')->nullable()->after('examiner_user_id');
            $table->unsignedSmallInteger('tajweed_score')->nullable()->after('memorization_score');
            $table->unsignedSmallInteger('review_score')->nullable()->after('tajweed_score');
            $table->string('tested_surah', 150)->nullable()->after('review_score');
        });
    }

    public function down(): void
    {
        Schema::table('test_results', function (Blueprint $table) {
            $table->dropColumn(['memorization_score', 'tajweed_score', 'review_score', 'tested_surah']);
        });
    }
};
