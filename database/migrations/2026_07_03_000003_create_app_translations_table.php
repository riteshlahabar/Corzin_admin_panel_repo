<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_translations', function (Blueprint $table) {
            $table->id();
            $table->string('group_name')->default('common')->index();
            $table->string('translation_key')->unique();
            $table->text('en_value')->nullable();
            $table->text('hi_value')->nullable();
            $table->text('mr_value')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_translations');
    }
};