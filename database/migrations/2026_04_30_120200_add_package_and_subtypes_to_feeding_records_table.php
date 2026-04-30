<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feeding_records', function (Blueprint $table) {
            if (! Schema::hasColumn('feeding_records', 'feed_subtype_details')) {
                $table->json('feed_subtype_details')->nullable()->after('feed_type_id');
            }
            if (! Schema::hasColumn('feeding_records', 'package_quantity')) {
                $table->decimal('package_quantity', 12, 2)->default(0)->after('quantity');
            }
            if (! Schema::hasColumn('feeding_records', 'feeding_quantity')) {
                $table->decimal('feeding_quantity', 12, 2)->nullable()->after('package_quantity');
            }
            if (! Schema::hasColumn('feeding_records', 'balance_quantity')) {
                $table->decimal('balance_quantity', 12, 2)->nullable()->after('feeding_quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('feeding_records', function (Blueprint $table) {
            if (Schema::hasColumn('feeding_records', 'balance_quantity')) {
                $table->dropColumn('balance_quantity');
            }
            if (Schema::hasColumn('feeding_records', 'feeding_quantity')) {
                $table->dropColumn('feeding_quantity');
            }
            if (Schema::hasColumn('feeding_records', 'package_quantity')) {
                $table->dropColumn('package_quantity');
            }
            if (Schema::hasColumn('feeding_records', 'feed_subtype_details')) {
                $table->dropColumn('feed_subtype_details');
            }
        });
    }
};

