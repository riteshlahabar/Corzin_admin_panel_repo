<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('st_states', function (Blueprint $table) {
            $table->id();
            $table->string('serial_no', 30)->nullable();
            $table->string('state_code', 20)->nullable();
            $table->string('state_version', 20)->nullable();
            $table->string('state_name', 150)->nullable();
            $table->string('state_name_alt', 150)->nullable();
            $table->string('census_2001_code', 20)->nullable();
            $table->string('census_2011_code', 20)->nullable();
            $table->string('state_or_ut', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('st_districts', function (Blueprint $table) {
            $table->id();
            $table->string('state_code', 20)->nullable();
            $table->string('state_name', 150)->nullable();
            $table->string('district_code', 20)->nullable();
            $table->string('district_name', 150)->nullable();
            $table->string('census_2001_code', 20)->nullable();
            $table->string('census_2011_code', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('st_subdistricts', function (Blueprint $table) {
            $table->id();
            $table->string('serial_no', 30)->nullable();
            $table->string('state_code', 20)->nullable();
            $table->string('state_name', 150)->nullable();
            $table->string('district_code', 20)->nullable();
            $table->string('district_name', 150)->nullable();
            $table->string('subdistrict_code', 20)->nullable();
            $table->string('subdistrict_name', 200)->nullable();
            $table->string('subdistrict_version', 20)->nullable();
            $table->string('census_2001_code', 20)->nullable();
            $table->string('census_2011_code', 20)->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('st_subdistricts');
        Schema::dropIfExists('st_districts');
        Schema::dropIfExists('st_states');
    }
};
