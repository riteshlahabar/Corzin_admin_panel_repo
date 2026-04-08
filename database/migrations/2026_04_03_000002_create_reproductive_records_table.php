<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reproductive_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->unsignedInteger('lactation_number')->nullable();
            $table->date('ai_date')->nullable();
            $table->string('breed_name')->nullable();
            $table->boolean('pregnancy_confirmation')->nullable();
            $table->date('calving_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reproductive_records');
    }
};
