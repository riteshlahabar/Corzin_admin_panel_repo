<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            if (! Schema::hasColumn('farmers', 'fcm_token')) {
                $table->text('fcm_token')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            if (Schema::hasColumn('farmers', 'fcm_token')) {
                $table->dropColumn('fcm_token');
            }
        });
    }
};

