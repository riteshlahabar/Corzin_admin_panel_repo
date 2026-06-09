<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('shop_units', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('shop_products', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('name');
            $table->string('hsn_code', 100)->nullable()->after('company_name');
        });

        $now = now();

        $categories = DB::table('shop_products')
            ->select('category')
            ->whereNotNull('category')
            ->get()
            ->pluck('category')
            ->map(fn ($value) => strtolower(trim((string) $value)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (! empty($categories)) {
            DB::table('shop_categories')->insert(
                collect($categories)->map(fn ($name) => [
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        }

        $units = DB::table('shop_products')
            ->select('unit')
            ->whereNotNull('unit')
            ->get()
            ->pluck('unit')
            ->map(fn ($value) => strtolower(trim((string) $value)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (! empty($units)) {
            DB::table('shop_units')->insert(
                collect($units)->map(fn ($name) => [
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        }
    }

    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropColumn(['company_name', 'hsn_code']);
        });

        Schema::dropIfExists('shop_units');
        Schema::dropIfExists('shop_categories');
    }
};
