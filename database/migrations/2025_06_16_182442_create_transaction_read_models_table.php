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
        Schema::create('transaction_read_models', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('account_uuid');
            $table->string('type', 50); // deposit, withdrawal, transfer_in, transfer_out
            $table->bigInteger('amount'); // Amount in cents
            $table->string('asset_code', 10)->default('USD'); // Asset code for multi-asset support
            $table->decimal('exchange_rate', 20, 10)->nullable(); // Exchange rate if cross-asset
            $table->string('reference_currency', 10)->nullable(); // Reference currency for cross-asset
            $table->bigInteger('reference_amount')->nullable(); // Amount in reference currency
            $table->text('description')->nullable();
            $table->uuid('related_transaction_uuid')->nullable(); // For linking transfer pairs
            $table->uuid('initiated_by')->nullable(); // User who initiated the transaction
            $table->string('status', 20)->default('completed'); // completed, pending, failed, reversed
            $table->json('metadata')->nullable(); // Additional transaction data
            $table->string('hash', 128)->nullable(); // Transaction hash for verification
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('account_uuid');
            $table->index('type');
            $table->index('status');
            $table->index('asset_code');
            $table->index('related_transaction_uuid');
            $table->index('processed_at');
            $table->index(['account_uuid', 'processed_at']); // For account history queries
            $table->index(['type', 'status', 'processed_at']); // For reporting queries
            
            // Foreign key constraints
            $table->foreign('account_uuid')->references('uuid')->on('accounts')->onDelete('cascade');
            $table->foreign('asset_code')->references('code')->on('assets')->onDelete('restrict');
            $table->foreign('initiated_by')->references('uuid')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_read_models');
    }
};
