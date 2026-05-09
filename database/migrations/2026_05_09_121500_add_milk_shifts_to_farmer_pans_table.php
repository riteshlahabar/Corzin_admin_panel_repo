<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmer_pans', function (Blueprint $table) {
            if (! Schema::hasColumn('farmer_pans', 'milk_shifts')) {
                $table->json('milk_shifts')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('farmer_pans', function (Blueprint $table) {
            if (Schema::hasColumn('farmer_pans', 'milk_shifts')) {
                $table->dropColumn('milk_shifts');
            }
        });
    }
};
