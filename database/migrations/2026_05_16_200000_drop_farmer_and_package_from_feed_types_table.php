<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old scoped-unique index if it exists.
        try {
            DB::statement('ALTER TABLE feed_types DROP INDEX feed_types_farmer_name_unique');
        } catch (\Throwable $e) {
            // ignore if not present
        }

        Schema::table('feed_types', function (Blueprint $table) {
            if (Schema::hasColumn('feed_types', 'package_quantity')) {
                $table->dropColumn('package_quantity');
            }

            if (Schema::hasColumn('feed_types', 'farmer_id')) {
                try {
                    $table->dropIndex(['farmer_id']);
                } catch (\Throwable $e) {
                    // ignore if index name differs or absent
                }
                $table->dropColumn('farmer_id');
            }
        });

        // Keep (or restore) global unique by name for admin-managed types.
        try {
            DB::statement('ALTER TABLE feed_types ADD UNIQUE feed_types_name_unique (name)');
        } catch (\Throwable $e) {
            // ignore if already exists or data has duplicates
        }
    }

    public function down(): void
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

        try {
            DB::statement('ALTER TABLE feed_types DROP INDEX feed_types_name_unique');
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            DB::statement('ALTER TABLE feed_types ADD UNIQUE feed_types_farmer_name_unique (farmer_id, name)');
        } catch (\Throwable $e) {
            // ignore
        }
    }
};

