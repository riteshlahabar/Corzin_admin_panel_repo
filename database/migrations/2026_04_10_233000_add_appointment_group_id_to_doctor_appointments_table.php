<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('doctor_appointments', 'appointment_group_id')) {
                $table->string('appointment_group_id', 64)->nullable()->index()->after('doctor_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctor_appointments', function (Blueprint $table) {
            if (Schema::hasColumn('doctor_appointments', 'appointment_group_id')) {
                $table->dropColumn('appointment_group_id');
            }
        });
    }
};
