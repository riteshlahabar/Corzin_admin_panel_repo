<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_settings', function (Blueprint $table) {
            $table->id();
            $table->longText('terms_and_conditions')->nullable();
            $table->longText('privacy_policy')->nullable();
            $table->timestamps();
        });

        DB::table('doctor_settings')->insert([
            'terms_and_conditions' => 'Default terms and conditions for doctor app.',
            'privacy_policy' => 'Default privacy policy for doctor app.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_settings');
    }
};

