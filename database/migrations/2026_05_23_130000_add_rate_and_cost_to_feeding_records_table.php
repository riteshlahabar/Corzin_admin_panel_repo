<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feeding_records', function (Blueprint $table) {
            if (! Schema::hasColumn('feeding_records', 'rate_per_unit')) {
                $table->decimal('rate_per_unit', 12, 2)->default(0)->after('balance_quantity');
            }
            if (! Schema::hasColumn('feeding_records', 'feeding_cost')) {
                $table->decimal('feeding_cost', 12, 2)->default(0)->after('rate_per_unit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('feeding_records', function (Blueprint $table) {
            if (Schema::hasColumn('feeding_records', 'feeding_cost')) {
                $table->dropColumn('feeding_cost');
            }
            if (Schema::hasColumn('feeding_records', 'rate_per_unit')) {
                $table->dropColumn('rate_per_unit');
            }
        });
    }
};

