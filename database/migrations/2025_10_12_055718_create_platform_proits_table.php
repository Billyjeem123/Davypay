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
        Schema::create('platform_revenues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // who made the transaction
            $table->string('transaction_id')->unique();
            $table->string('product_name');
            $table->string('platform');
            $table->string('type')->nullable();
            $table->string('status'); // delivered, failed, etc.
            $table->decimal('amount', 12, 2); // total amount paid by user
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('commission', 12, 2)->default(0); // VTpass commission
            $table->decimal('profit', 12, 2)->default(0); // your profit
            $table->string('unique_element')->nullable(); // phone, meter number, etc.
            $table->string('channel')->nullable(); // api, web, etc.
            $table->string('response_code')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_revenues');
    }
};
