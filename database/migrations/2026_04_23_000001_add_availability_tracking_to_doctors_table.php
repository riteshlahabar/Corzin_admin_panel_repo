<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (! Schema::hasColumn('doctors', 'is_active_for_appointments')) {
                $table->boolean('is_active_for_appointments')
                    ->default(false)
                    ->after('status');
            }

            if (! Schema::hasColumn('doctors', 'last_live_location_at')) {
                $table->timestamp('last_live_location_at')
                    ->nullable()
                    ->after('longitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (Schema::hasColumn('doctors', 'last_live_location_at')) {
                $table->dropColumn('last_live_location_at');
            }
            if (Schema::hasColumn('doctors', 'is_active_for_appointments')) {
                $table->dropColumn('is_active_for_appointments');
            }
        });
    }
};

