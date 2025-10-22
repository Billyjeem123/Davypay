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
        Schema::create('naira_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('email');
            $table->string('phone');
            $table->string('nin');
            $table->date('dob');
            $table->string('name');
            $table->string('line')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('nearest_bus_stop')->nullable();
            $table->string('house_no')->nullable();
            $table->string('postcode')->nullable();
            $table->string('billing_zip_code')->nullable();
            $table->string('billing_country')->default('NG');
            $table->string('customer_id')->nullable();
            $table->string('card_id')->nullable();
            $table->string('brand')->nullable();
            $table->string('type')->nullable();
            $table->string('mask')->nullable();
            $table->string('number')->nullable();
            $table->string('expiration')->nullable();
            $table->string('status')->default('pending');
            $table->string('card_status')->default('pending');
            $table->string('provider')->default('strowallet');
            $table->json('api_response')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('naira_cards');
    }
};
