<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            if (! Schema::hasColumn('farmers', 'farmer_photo')) {
                $table->string('farmer_photo')->nullable()->after('pincode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            if (Schema::hasColumn('farmers', 'farmer_photo')) {
                $table->dropColumn('farmer_photo');
            }
        });
    }
};
