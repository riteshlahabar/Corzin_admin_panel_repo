<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dairies', function (Blueprint $table) {
            $table->string('gst_no')->nullable()->after('dairy_name');
            $table->string('city')->nullable()->after('address');
            $table->string('taluka')->nullable()->after('city');
            $table->string('district')->nullable()->after('taluka');
            $table->string('state')->nullable()->after('district');
            $table->string('pincode')->nullable()->after('state');
        });
    }

    public function down(): void
    {
        Schema::table('dairies', function (Blueprint $table) {
            $table->dropColumn(['gst_no', 'city', 'taluka', 'district', 'state', 'pincode']);
        });
    }
};
