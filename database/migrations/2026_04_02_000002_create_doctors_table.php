<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('clinic_name');
            $table->string('degree');
            $table->string('contact_number', 30);
            $table->string('email')->unique();
            $table->string('adhar_number');
            $table->string('pan_number');
            $table->string('mmc_registration_number');
            $table->string('clinic_registration_number');
            $table->text('clinic_address');
            $table->string('village');
            $table->string('city');
            $table->string('taluka');
            $table->string('district');
            $table->string('state');
            $table->string('pincode', 15);
            $table->string('adhar_document');
            $table->string('pan_document');
            $table->string('mmc_document');
            $table->string('clinic_registration_document');
            $table->string('doctor_photo');
            $table->string('status')->default('pending');
            $table->text('status_message')->nullable();
            $table->string('password');
            $table->boolean('terms_accepted')->default(false);
            $table->text('terms_text')->nullable();
            $table->text('fcm_token')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
