<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('default_unit', 20)->default('Kg');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('feed_types')->insert([
            ['name' => 'Green Fodder', 'default_unit' => 'Kg', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dry Fodder', 'default_unit' => 'Kg', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Concentrate Feed', 'default_unit' => 'Kg', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mineral Mixture', 'default_unit' => 'Gram', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Supplements', 'default_unit' => 'Gram', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_types');
    }
};
