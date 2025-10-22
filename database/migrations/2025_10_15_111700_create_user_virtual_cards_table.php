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
        Schema::create('user_virtual_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade'); // deletes cards when user is deleted
            $table->string('card_id')->unique();
            $table->string('card_status')->nullable();
            $table->string('name')->nullable();
            $table->string('brand')->nullable();
            $table->string('type')->nullable();
            $table->string('reference')->nullable();
            $table->string('status')->default('pending');
            $table->string('customer_id')->nullable();
            $table->string('provider_user_id')->nullable();
            $table->json('api_response')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_virtual_cards');
    }
};
