<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE feed_diet_plans MODIFY days_count INT UNSIGNED NULL DEFAULT NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE feed_diet_plans SET days_count = 1 WHERE days_count IS NULL');
        DB::statement('ALTER TABLE feed_diet_plans MODIFY days_count INT UNSIGNED NOT NULL DEFAULT 1');
    }
};

