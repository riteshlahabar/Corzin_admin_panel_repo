<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            if (! Schema::hasColumn('farmers', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('pincode');
            }
            if (! Schema::hasColumn('farmers', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (! Schema::hasColumn('farmers', 'current_location_address')) {
                $table->string('current_location_address')->nullable()->after('longitude');
            }

            $table->index(['latitude', 'longitude'], 'farmers_lat_lng_idx');
        });
    }

    public function down(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            $table->dropIndex('farmers_lat_lng_idx');
            $table->dropColumn(['current_location_address', 'latitude', 'longitude']);
        });
    }
};

