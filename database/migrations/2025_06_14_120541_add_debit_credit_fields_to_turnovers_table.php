<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('turnovers', function (Blueprint $table) {
            $table->decimal('debit', 15, 2)->default(0)->after('amount');
            $table->decimal('credit', 15, 2)->default(0)->after('debit');
        });

        // Migrate existing data: positive amounts go to credit, negative to debit
        DB::statement('UPDATE turnovers SET credit = amount WHERE amount > 0');
        DB::statement('UPDATE turnovers SET debit = ABS(amount) WHERE amount < 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turnovers', function (Blueprint $table) {
            $table->dropColumn(['debit', 'credit']);
        });
    }
};
