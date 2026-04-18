<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_appointments', function (Blueprint $table) {
            $table->decimal('fees', 10, 2)->nullable()->after('charges');
            $table->decimal('on_site_medicine_charges', 10, 2)->nullable()->after('fees');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_appointments', function (Blueprint $table) {
            $table->dropColumn(['fees', 'on_site_medicine_charges']);
        });
    }
};

