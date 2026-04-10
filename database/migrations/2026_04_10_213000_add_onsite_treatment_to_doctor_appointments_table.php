<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('doctor_appointments', 'onsite_treatment')) {
                $table->text('onsite_treatment')->nullable()->after('treatment_details');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctor_appointments', function (Blueprint $table) {
            if (Schema::hasColumn('doctor_appointments', 'onsite_treatment')) {
                $table->dropColumn('onsite_treatment');
            }
        });
    }
};
