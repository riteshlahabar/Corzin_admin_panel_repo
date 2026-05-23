<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pan_milk_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('farmers')->cascadeOnDelete();
            $table->foreignId('pan_id')->constrained('farmer_pans')->cascadeOnDelete();
            $table->foreignId('dairy_id')->nullable()->constrained('dairies')->nullOnDelete();
            $table->date('date');
            $table->enum('shift', ['Morning', 'Afternoon', 'Evening']);
            $table->decimal('quantity_liters', 10, 2);
            $table->decimal('cow_total_liters', 10, 2)->default(0);
            $table->decimal('fat', 8, 2)->nullable();
            $table->decimal('snf', 8, 2)->nullable();
            $table->decimal('rate', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['farmer_id', 'date', 'shift']);
            $table->index(['pan_id', 'date', 'shift']);
        });

        Schema::create('pan_milk_entry_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pan_milk_entry_id')->constrained('pan_milk_entries')->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->foreignId('milk_production_id')->nullable()->constrained('milk_productions')->nullOnDelete();
            $table->decimal('default_milk_per_session', 8, 2)->nullable();
            $table->decimal('final_milk_qty', 10, 2);
            $table->timestamps();

            $table->unique(['pan_milk_entry_id', 'animal_id'], 'pan_milk_entry_animal_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pan_milk_entry_details');
        Schema::dropIfExists('pan_milk_entries');
    }
};
