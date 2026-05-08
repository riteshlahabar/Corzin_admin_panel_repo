<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('doctor_appointments', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctor_appointments', function (Blueprint $table) {
            if (Schema::hasColumn('doctor_appointments', 'accepted_at')) {
                $table->dropColumn('accepted_at');
            }
        });
    }
};
