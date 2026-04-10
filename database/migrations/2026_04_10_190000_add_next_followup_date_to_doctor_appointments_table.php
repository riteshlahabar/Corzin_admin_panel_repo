<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('doctor_appointments', 'next_followup_date')) {
                $table->date('next_followup_date')->nullable()->after('followup_required');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctor_appointments', function (Blueprint $table) {
            if (Schema::hasColumn('doctor_appointments', 'next_followup_date')) {
                $table->dropColumn('next_followup_date');
            }
        });
    }
};
