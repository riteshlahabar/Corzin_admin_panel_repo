<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feed_diet_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('feed_diet_plans', 'diet_plan_name')) {
                $table->string('diet_plan_name')->nullable()->after('animal_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('feed_diet_plans', function (Blueprint $table) {
            if (Schema::hasColumn('feed_diet_plans', 'diet_plan_name')) {
                $table->dropColumn('diet_plan_name');
            }
        });
    }
};

