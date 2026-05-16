<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmer_pans', function (Blueprint $table) {
            if (! Schema::hasColumn('farmer_pans', 'pan_type')) {
                $table->string('pan_type', 30)->default('milking')->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('farmer_pans', function (Blueprint $table) {
            if (Schema::hasColumn('farmer_pans', 'pan_type')) {
                $table->dropColumn('pan_type');
            }
        });
    }
};

