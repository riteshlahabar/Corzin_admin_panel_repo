<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('doctor_appointments', 'followup_notified_on')) {
                $table->date('followup_notified_on')->nullable()->after('next_followup_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctor_appointments', function (Blueprint $table) {
            if (Schema::hasColumn('doctor_appointments', 'followup_notified_on')) {
                $table->dropColumn('followup_notified_on');
            }
        });
    }
};
