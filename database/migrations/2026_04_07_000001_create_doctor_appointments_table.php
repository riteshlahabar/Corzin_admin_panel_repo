<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('farmer_id')->nullable()->constrained('farmers')->nullOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->string('farmer_name')->nullable();
            $table->string('farmer_phone', 30)->nullable();
            $table->string('animal_name')->nullable();
            $table->string('animal_photo')->nullable();
            $table->text('concern');
            $table->string('status')->default('pending');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->decimal('charges', 10, 2)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('farmer_approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['doctor_id', 'status']);
            $table->index('requested_at');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_appointments');
    }
};

