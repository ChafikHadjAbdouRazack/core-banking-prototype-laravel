<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fraud_scores', function (Blueprint $table) {
            $table->json('analysis_results')->nullable()->after('network_factors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fraud_scores', function (Blueprint $table) {
            $table->dropColumn('analysis_results');
        });
    }
};
