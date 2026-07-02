<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('feed_subtypes', 'farmer_id')) {
            Schema::table('feed_subtypes', function (Blueprint $table) {
                $table->unsignedBigInteger('farmer_id')->nullable()->after('feed_type_id');
            });

            Schema::table('feed_subtypes', function (Blueprint $table) {
                $table->foreign('farmer_id')->references('id')->on('farmers')->nullOnDelete();
            });
        }

        try {
            DB::statement('ALTER TABLE feed_subtypes DROP INDEX feed_subtypes_feed_type_id_name_unique');
        } catch (\Throwable $exception) {
        }

        try {
            DB::statement('ALTER TABLE feed_subtypes ADD UNIQUE feed_subtypes_type_farmer_name_unique (feed_type_id, farmer_id, name)');
        } catch (\Throwable $exception) {
        }
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE feed_subtypes DROP INDEX feed_subtypes_type_farmer_name_unique');
        } catch (\Throwable $exception) {
        }

        try {
            DB::statement('ALTER TABLE feed_subtypes ADD UNIQUE feed_subtypes_feed_type_id_name_unique (feed_type_id, name)');
        } catch (\Throwable $exception) {
        }

        if (Schema::hasColumn('feed_subtypes', 'farmer_id')) {
            Schema::table('feed_subtypes', function (Blueprint $table) {
                $table->dropForeign(['farmer_id']);
                $table->dropColumn('farmer_id');
            });
        }
    }
};
