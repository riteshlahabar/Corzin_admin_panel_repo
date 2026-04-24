<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            if (! Schema::hasColumn('shop_products', 'medicine_aliases')) {
                $table->text('medicine_aliases')->nullable()->after('features');
            }
            if (! Schema::hasColumn('shop_products', 'pack_size')) {
                $table->unsignedInteger('pack_size')->nullable()->after('medicine_aliases');
            }
            if (! Schema::hasColumn('shop_products', 'allow_partial_units')) {
                $table->boolean('allow_partial_units')->default(false)->after('pack_size');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            if (Schema::hasColumn('shop_products', 'allow_partial_units')) {
                $table->dropColumn('allow_partial_units');
            }
            if (Schema::hasColumn('shop_products', 'pack_size')) {
                $table->dropColumn('pack_size');
            }
            if (Schema::hasColumn('shop_products', 'medicine_aliases')) {
                $table->dropColumn('medicine_aliases');
            }
        });
    }
};

