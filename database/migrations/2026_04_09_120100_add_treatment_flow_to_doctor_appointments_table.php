<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_appointments', function (Blueprint $table) {
            $table->json('disease_ids')->nullable()->after('concern');
            $table->text('disease_details')->nullable()->after('disease_ids');
            $table->string('otp_code', 10)->nullable()->after('disease_details');
            $table->timestamp('otp_verified_at')->nullable()->after('otp_code');
            $table->timestamp('treatment_started_at')->nullable()->after('otp_verified_at');
            $table->longText('treatment_details')->nullable()->after('treatment_started_at');
            $table->boolean('followup_required')->default(false)->after('treatment_details');
            $table->decimal('doctor_live_latitude', 10, 7)->nullable()->after('followup_required');
            $table->decimal('doctor_live_longitude', 10, 7)->nullable()->after('doctor_live_latitude');
            $table->timestamp('doctor_live_updated_at')->nullable()->after('doctor_live_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_appointments', function (Blueprint $table) {
            $table->dropColumn([
                'disease_ids',
                'disease_details',
                'otp_code',
                'otp_verified_at',
                'treatment_started_at',
                'treatment_details',
                'followup_required',
                'doctor_live_latitude',
                'doctor_live_longitude',
                'doctor_live_updated_at',
            ]);
        });
    }
};
