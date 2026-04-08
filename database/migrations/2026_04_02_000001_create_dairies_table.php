<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dairies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('farmers')->cascadeOnDelete();
            $table->string('dairy_name');
            $table->string('contact_number')->nullable();
            $table->string('village')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dairies');
    }
};
