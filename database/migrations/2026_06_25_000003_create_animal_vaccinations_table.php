<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('farmers')->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->foreignId('pan_id')->nullable()->constrained('farmer_pans')->nullOnDelete();
            $table->string('pan_name')->nullable();
            $table->foreignId('vaccine_id')->constrained('vaccines')->cascadeOnDelete();
            $table->string('doses');
            $table->date('vaccination_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_vaccinations');
    }
};
