<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feed_diet_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('feed_diet_plans', 'pan_id')) {
                $table->unsignedBigInteger('pan_id')->nullable()->after('animal_id');
            }
            if (! Schema::hasColumn('feed_diet_plans', 'reference_date')) {
                $table->date('reference_date')->nullable()->after('feed_type_id');
            }
            if (! Schema::hasColumn('feed_diet_plans', 'body_weight')) {
                $table->decimal('body_weight', 12, 2)->default(0)->after('reference_date');
            }
            if (! Schema::hasColumn('feed_diet_plans', 'milk_production')) {
                $table->decimal('milk_production', 12, 2)->default(0)->after('body_weight');
            }
            if (! Schema::hasColumn('feed_diet_plans', 'target_dmi')) {
                $table->decimal('target_dmi', 12, 2)->default(0)->after('milk_production');
            }
            if (! Schema::hasColumn('feed_diet_plans', 'planned_dry_matter')) {
                $table->decimal('planned_dry_matter', 12, 2)->default(0)->after('target_dmi');
            }
            if (! Schema::hasColumn('feed_diet_plans', 'dmi_gap')) {
                $table->decimal('dmi_gap', 12, 2)->default(0)->after('planned_dry_matter');
            }
        });
    }

    public function down(): void
    {
        Schema::table('feed_diet_plans', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('feed_diet_plans', 'dmi_gap')) {
                $drop[] = 'dmi_gap';
            }
            if (Schema::hasColumn('feed_diet_plans', 'planned_dry_matter')) {
                $drop[] = 'planned_dry_matter';
            }
            if (Schema::hasColumn('feed_diet_plans', 'target_dmi')) {
                $drop[] = 'target_dmi';
            }
            if (Schema::hasColumn('feed_diet_plans', 'milk_production')) {
                $drop[] = 'milk_production';
            }
            if (Schema::hasColumn('feed_diet_plans', 'body_weight')) {
                $drop[] = 'body_weight';
            }
            if (Schema::hasColumn('feed_diet_plans', 'reference_date')) {
                $drop[] = 'reference_date';
            }
            if (Schema::hasColumn('feed_diet_plans', 'pan_id')) {
                $drop[] = 'pan_id';
            }
            if (! empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};

