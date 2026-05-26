<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_settings', function (Blueprint $table) {
            $table->id();
            $table->string('admin_contact_name')->nullable();
            $table->string('admin_contact_number', 30)->nullable();
            $table->timestamps();
        });

        DB::table('farmer_settings')->insert([
            'admin_contact_name' => 'Corzin Admin',
            'admin_contact_number' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_settings');
    }
};
