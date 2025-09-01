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
//        Schema::dropIfExists('nomba_transactions');
        Schema::create('nomba_transactions', function (Blueprint $table) {
            $table->id();

            // Core Identifiers
            $table->string('transaction_id')->unique(); // transaction.transactionId
            $table->string('event_type')->nullable()->comment('e.g. payment_success');
            $table->string('request_id')->unique(); // Nomba request ID
            $table->string('reference')->index(); // order.orderReference
            $table->string('order_id')->nullable(); // order.orderId

            $table->string('merchant_tx_ref')->nullable(); // transaction.merchantTxRef

            // Transaction Details
            $table->decimal('amount', 20, 2); // order.amount
            $table->decimal('fee', 20, 2)->nullable(); // transaction.fee
            $table->string('currency', 3)->default('NGN'); // order.currency
            $table->string('payment_method')->nullable(); // order.paymentMethod
            $table->string('channel')->nullable(); // transaction.originatingFrom
            $table->string('transaction_type')->nullable(); // transaction.type
            $table->string('status')->default('success'); // inferred from event_type
            $table->timestamp('paid_at')->nullable(); // transaction.time

            // Card/Authorization
            $table->string('card_type')->nullable(); // tokenizedCardData.cardType
            $table->string('card_last4')->nullable(); // order.cardLast4Digits
            $table->string('card_issuer')->nullable(); // transaction.cardIssuer
            $table->json('tokenized_card_data')->nullable(); // full object (for reference)

            // Merchant
            $table->string('wallet_id')->nullable(); // merchant.walletId
            $table->string('merchant_user_id')->nullable(); // merchant.userId
            $table->decimal('wallet_balance', 20, 2)->nullable(); // merchant.walletBalance

            // Customer Info
            $table->string('customer_email')->nullable();
            $table->string('customer_id')->nullable();
            $table->json('customer_data')->nullable(); // extra customer fields

            // Raw Payload
            $table->json('metadata')->nullable(); // for audit or replay

            // Foreign key to user (optional)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('SET NULL');

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('transaction_id');
            $table->index('merchant_tx_ref');
            $table->index('status');
            $table->index('customer_email');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomba_transactions');
    }
};
