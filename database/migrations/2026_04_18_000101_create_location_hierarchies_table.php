<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_hierarchies', function (Blueprint $table) {
            $table->id();
            $table->string('state');
            $table->string('district');
            $table->string('taluka');
            $table->string('city');
            $table->timestamps();

            $table->index(['state', 'district']);
            $table->index(['state', 'district', 'taluka']);
            $table->unique(['state', 'district', 'taluka', 'city'], 'location_hierarchy_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_hierarchies');
    }
};

