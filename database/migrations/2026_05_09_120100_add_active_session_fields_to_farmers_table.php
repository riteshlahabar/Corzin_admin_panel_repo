<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            if (! Schema::hasColumn('farmers', 'active_device_id')) {
                $table->string('active_device_id', 120)->nullable()->after('fcm_token');
            }
            if (! Schema::hasColumn('farmers', 'active_session_token')) {
                $table->string('active_session_token', 120)->nullable()->after('active_device_id');
            }
            if (! Schema::hasColumn('farmers', 'active_session_at')) {
                $table->timestamp('active_session_at')->nullable()->after('active_session_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            if (Schema::hasColumn('farmers', 'active_session_at')) {
                $table->dropColumn('active_session_at');
            }
            if (Schema::hasColumn('farmers', 'active_session_token')) {
                $table->dropColumn('active_session_token');
            }
            if (Schema::hasColumn('farmers', 'active_device_id')) {
                $table->dropColumn('active_device_id');
            }
        });
    }
};
