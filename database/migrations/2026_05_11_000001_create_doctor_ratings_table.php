<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_appointment_id')->unique()->constrained('doctor_appointments')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('farmer_id')->nullable()->constrained('farmers')->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->timestamps();

            $table->index(['doctor_id', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_ratings');
    }
};
