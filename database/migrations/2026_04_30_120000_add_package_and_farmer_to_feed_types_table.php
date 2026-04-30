<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feed_types', function (Blueprint $table) {
            if (! Schema::hasColumn('feed_types', 'farmer_id')) {
                $table->unsignedBigInteger('farmer_id')->nullable()->after('id');
                $table->index('farmer_id');
            }
            if (! Schema::hasColumn('feed_types', 'package_quantity')) {
                $table->decimal('package_quantity', 12, 2)->default(0)->after('default_unit');
            }
        });

        // Replace global unique(name) with scoped unique(farmer_id, name)
        // so each farmer can manage their own feed types safely.
        try {
            DB::statement('ALTER TABLE feed_types DROP INDEX feed_types_name_unique');
        } catch (\Throwable $e) {
            // ignore if index is already dropped / named differently
        }

        try {
            DB::statement('ALTER TABLE feed_types ADD UNIQUE feed_types_farmer_name_unique (farmer_id, name)');
        } catch (\Throwable $e) {
            // ignore if already exists
        }
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE feed_types DROP INDEX feed_types_farmer_name_unique');
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            DB::statement('ALTER TABLE feed_types ADD UNIQUE feed_types_name_unique (name)');
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::table('feed_types', function (Blueprint $table) {
            if (Schema::hasColumn('feed_types', 'package_quantity')) {
                $table->dropColumn('package_quantity');
            }
            if (Schema::hasColumn('feed_types', 'farmer_id')) {
                $table->dropIndex(['farmer_id']);
                $table->dropColumn('farmer_id');
            }
        });
    }
};

