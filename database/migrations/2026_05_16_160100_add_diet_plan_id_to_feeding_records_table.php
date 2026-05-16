<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feeding_records', function (Blueprint $table) {
            if (! Schema::hasColumn('feeding_records', 'diet_plan_id')) {
                $table->unsignedBigInteger('diet_plan_id')->nullable()->after('feed_type_id');
                $table->foreign('diet_plan_id')
                    ->references('id')
                    ->on('feed_diet_plans')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('feeding_records', function (Blueprint $table) {
            if (Schema::hasColumn('feeding_records', 'diet_plan_id')) {
                $table->dropForeign(['diet_plan_id']);
                $table->dropColumn('diet_plan_id');
            }
        });
    }
};

