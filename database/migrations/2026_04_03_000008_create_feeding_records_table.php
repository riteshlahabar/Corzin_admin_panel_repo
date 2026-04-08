<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feeding_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('farmers')->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->foreignId('feed_type_id')->constrained('feed_types')->cascadeOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 20);
            $table->enum('feeding_time', ['Morning', 'Afternoon', 'Evening'])->default('Morning');
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feeding_records');
    }
};
