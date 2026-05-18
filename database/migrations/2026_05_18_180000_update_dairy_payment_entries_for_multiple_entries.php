<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dairy_payment_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('dairy_payment_entries', 'opening_balance')) {
                $table->decimal('opening_balance', 12, 2)->default(0)->after('payment_date');
            }
            if (! Schema::hasColumn('dairy_payment_entries', 'day_total_amount')) {
                $table->decimal('day_total_amount', 12, 2)->default(0)->after('opening_balance');
            }
            if (! Schema::hasColumn('dairy_payment_entries', 'closing_balance')) {
                $table->decimal('closing_balance', 12, 2)->default(0)->after('paid_amount');
            }
        });

        try {
            DB::statement('ALTER TABLE `dairy_payment_entries` DROP INDEX `dairy_payment_entries_unique_day`');
        } catch (\Throwable $e) {
            // Index might already be removed.
        }
    }

    public function down(): void
    {
        Schema::table('dairy_payment_entries', function (Blueprint $table) {
            if (Schema::hasColumn('dairy_payment_entries', 'opening_balance')) {
                $table->dropColumn('opening_balance');
            }
            if (Schema::hasColumn('dairy_payment_entries', 'day_total_amount')) {
                $table->dropColumn('day_total_amount');
            }
            if (Schema::hasColumn('dairy_payment_entries', 'closing_balance')) {
                $table->dropColumn('closing_balance');
            }
        });

        try {
            DB::statement('ALTER TABLE `dairy_payment_entries` ADD UNIQUE `dairy_payment_entries_unique_day` (`farmer_id`, `dairy_id`, `payment_date`)');
        } catch (\Throwable $e) {
            // Re-adding may fail when duplicates already exist.
        }
    }
};

