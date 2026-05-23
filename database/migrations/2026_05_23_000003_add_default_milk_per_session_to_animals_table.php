<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            if (! Schema::hasColumn('animals', 'default_milk_per_session')) {
                $table->decimal('default_milk_per_session', 8, 2)->nullable()->after('weight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            if (Schema::hasColumn('animals', 'default_milk_per_session')) {
                $table->dropColumn('default_milk_per_session');
            }
        });
    }
};
