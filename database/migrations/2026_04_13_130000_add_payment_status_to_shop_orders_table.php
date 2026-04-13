<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('shop_orders', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('payment_method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            if (Schema::hasColumn('shop_orders', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });
    }
};
