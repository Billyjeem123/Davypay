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
        Schema::dropIfExists('kyc');
        Schema::create('kyc', function (Blueprint $table) {
            $table->id();

            // Webhook payload storage
            $table->json('webhook')->nullable()->comment('Full webhook payload');

            // User relationship
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Basic KYC fields (Tier 1 & 2)
            $table->string('bvn')->nullable()->unique();
            $table->string('nin')->nullable();
            $table->string('selfie')->nullable();
            $table->string('utility_bill')->nullable();
            $table->string('address')->nullable();
            $table->string('admin_remark')->nullable();
            $table->enum('tier', ['tier_1', 'tier_2', 'tier_3'])->nullable();
            $table->string('status', 50)->default('pending');
            $table->string('phone_number', 20)->nullable();
            $table->string('selfie_confidence')->nullable();
            $table->string('selfie_match')->nullable();
            $table->longText('id_image')->nullable();
            $table->longText('zipcode')->nullable();
            $table->string('selfie_image')->nullable();
            $table->string('verification_image')->nullable();
            $table->string('nationality')->nullable();
            $table->string('dob')->nullable();

            // Tier 3 specific fields - Document information
            $table->string('document_type', 50)->nullable()->comment('Type of ID document (Passport, Driver License, etc.)');
            $table->string('document_number')->nullable()->comment('ID document number');
            $table->string('date_issued')->nullable()->comment('Date when document was issued');
            $table->string('expiry_date')->nullable()->comment('Document expiry date');
            $table->string('middle_name')->nullable()->comment('Middle name from document');

            // Tier 3 - Image URLs
            $table->string('id_image_url', 500)->nullable()->comment('URL of the ID document image');
            $table->string('id_back_url', 500)->nullable()->comment('URL of the back of ID document');

            // Tier 3 - Verification metadata
            $table->string('mrz_status', 50)->nullable()->comment('Machine Readable Zone status');
            $table->string('reference_id')->nullable()->comment('Dojah reference ID');
            $table->string('widget_id')->nullable()->comment('Dojah widget ID');
            $table->string('verification_mode', 50)->nullable()->comment('Verification mode (LIVENESS, etc.)');
            $table->string('verification_type', 50)->nullable()->comment('Type of verification (PASSPORT_ID, etc.)');
            $table->string('verification_value')->nullable()->comment('Value used for verification');

            // Tier 3 - Status fields
            $table->boolean('aml_status')->nullable()->comment('Anti-Money Laundering check status');
            $table->boolean('address_verification_status')->nullable()->comment('Address verification status');

            // Tier 3 - Location data for address verification
            $table->string('user_latitude', 50)->nullable()->comment('User location latitude');
            $table->string('user_longitude', 50)->nullable()->comment('User location longitude');
            $table->string('user_location_name')->nullable()->comment('User location name');
            $table->string('address_latitude', 50)->nullable()->comment('Address location latitude');
            $table->string('address_longitude', 50)->nullable()->comment('Address location longitude');

            // Additional data
            $table->json('extras')->nullable()->comment('Additional document data');

            $table->timestamps();

            // Indexes
            $table->unique('document_number', 'kyc_document_number_unique');
            $table->index('reference_id', 'kyc_reference_id_index');
            $table->index('user_id', 'kyc_user_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc');
    }
};
