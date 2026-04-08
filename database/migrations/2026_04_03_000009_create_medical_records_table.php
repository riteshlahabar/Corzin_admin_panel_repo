<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('farmers')->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->string('medicine_name');
            $table->string('dose');
            $table->date('date');
            $table->string('disease');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
