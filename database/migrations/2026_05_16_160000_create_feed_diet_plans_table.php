<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_diet_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('farmer_id');
            $table->unsignedBigInteger('animal_id');
            $table->unsignedBigInteger('feed_type_id');
            $table->unsignedInteger('days_count')->default(1);
            $table->decimal('plan_quantity', 12, 2)->default(0);
            $table->decimal('consumed_quantity', 12, 2)->default(0);
            $table->decimal('remaining_quantity', 12, 2)->default(0);
            $table->string('unit', 30)->default('Kg');
            $table->json('subtype_details')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('farmer_id')->references('id')->on('farmers')->cascadeOnDelete();
            $table->foreign('animal_id')->references('id')->on('animals')->cascadeOnDelete();
            $table->foreign('feed_type_id')->references('id')->on('feed_types')->cascadeOnDelete();
            $table->index(['farmer_id', 'animal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_diet_plans');
    }
};

