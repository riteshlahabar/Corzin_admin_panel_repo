<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('farmers', function (Blueprint $table) {
    $table->id();
    $table->string('mobile')->unique();
  
    $table->string('first_name')->nullable();
    $table->string('middle_name')->nullable();
    $table->string('last_name')->nullable();

    $table->string('village')->nullable();
    $table->string('city')->nullable();
    $table->string('taluka')->nullable();
    $table->string('district')->nullable();
    $table->string('state')->nullable();
    $table->string('pincode')->nullable();
  
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farmers');
    }
};
