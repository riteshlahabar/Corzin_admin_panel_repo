<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('farmers', 'is_active')) {
            Schema::table('farmers', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('pincode');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('farmers', 'is_active')) {
            Schema::table('farmers', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
