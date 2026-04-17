<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_appointments', function (Blueprint $table) {
            $table->unsignedTinyInteger('notify_radius_from_km')->default(0)->after('appointment_group_id');
            $table->unsignedTinyInteger('notify_radius_to_km')->default(5)->after('notify_radius_from_km');
            $table->timestamp('notified_at')->nullable()->after('requested_at');

            $table->index(['appointment_group_id', 'status'], 'doctor_appt_group_status_idx');
            $table->index(['appointment_group_id', 'notified_at'], 'doctor_appt_group_notified_idx');
            $table->index(['notified_at', 'status'], 'doctor_appt_notified_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_appointments', function (Blueprint $table) {
            $table->dropIndex('doctor_appt_group_status_idx');
            $table->dropIndex('doctor_appt_group_notified_idx');
            $table->dropIndex('doctor_appt_notified_status_idx');
            $table->dropColumn(['notify_radius_from_km', 'notify_radius_to_km', 'notified_at']);
        });
    }
};

