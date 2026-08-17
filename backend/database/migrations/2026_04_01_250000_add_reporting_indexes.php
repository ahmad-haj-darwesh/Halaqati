<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // daily_evaluation_reason: index for reporting breakdown
        Schema::table('daily_evaluation_reason', function (Blueprint $table) {
            $table->index('evaluation_reason_id', 'der_evaluation_reason_id_idx');
        });

        // test_results: examiner_user_id + tested_at
        Schema::table('test_results', function (Blueprint $table) {
            $table->index(['examiner_user_id', 'tested_at'], 'tr_examiner_tested_at_idx');
        });

        // supervisory_visit_scores: item id for averages
        Schema::table('supervisory_visit_scores', function (Blueprint $table) {
            $table->index('supervision_rubric_item_id', 'svs_item_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('daily_evaluation_reason', function (Blueprint $table) {
            $table->dropIndex('der_evaluation_reason_id_idx');
        });

        Schema::table('test_results', function (Blueprint $table) {
            $table->dropIndex('tr_examiner_tested_at_idx');
        });

        Schema::table('supervisory_visit_scores', function (Blueprint $table) {
            $table->dropIndex('svs_item_id_idx');
        });
    }
};

