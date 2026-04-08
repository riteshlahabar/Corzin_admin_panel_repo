<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->id();

            // 🔗 Farmer Relation
            $table->foreignId('farmer_id')
                  ->constrained('farmers')
                  ->cascadeOnDelete();

            // 🔑 Basic Info
            $table->string('unique_id')->unique();
            $table->string('animal_name');
            $table->string('tag_number')->unique();

            // 🔗 Animal Type Relation
            $table->foreignId('animal_type_id')
                  ->constrained('animal_types')
                  ->cascadeOnDelete();

            // 📊 Details
            $table->integer('age')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['Male', 'Female']);
            $table->decimal('weight', 8, 2)->nullable(); // better than float

            // 🖼 Image
            $table->string('image')->nullable();

            $table->timestamps();

            // ⚡ Indexes (performance)
            $table->index('farmer_id');
            $table->index('animal_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};