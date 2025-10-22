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
        Schema::create('esims', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();

            // SIM details
            $table->string('sim_id')->nullable();
            $table->string('iccid')->nullable();
            $table->string('product_id')->nullable();
            $table->string('imsi')->nullable();
            $table->string('state')->nullable();
            $table->timestamp('last_operation_date')->nullable();
            $table->string('activation_code')->nullable();
            $table->string('smdp')->nullable();
            $table->timestamp('purchase_date')->nullable();

            // Data Plan details
            $table->string('plan_product_id')->nullable();
            $table->string('plan_name')->nullable();
            $table->integer('data_usage_allowance')->nullable(); // in GB
            $table->integer('time_allowance')->nullable(); // in days
            $table->string('country')->nullable();
            $table->string('iso3')->nullable();
            $table->string('region')->nullable();

            $table->string('status')->nullable(); // response status
            $table->integer('response_code')->nullable();
            $table->string('response_message')->nullable();

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esims');
    }
};
