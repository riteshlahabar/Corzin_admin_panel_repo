<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('animal_pregnancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('farmers')->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->unsignedInteger('pregnancy_no')->default(1);
            $table->unsignedInteger('service_no')->default(1);
            $table->date('heat_date')->nullable();
            $table->date('ai_date');
            $table->enum('service_type', ['ai', 'natural'])->default('ai');
            $table->string('bull_name')->nullable();
            $table->string('semen_no')->nullable();
            $table->string('doctor_name')->nullable();
            $table->date('pregnancy_check_due_date')->nullable();
            $table->date('pregnancy_check_date')->nullable();
            $table->enum('pregnancy_result', ['pending', 'pregnant', 'not_pregnant'])->default('pending');
            $table->date('expected_calving_date')->nullable();
            $table->date('dry_off_date')->nullable();
            $table->date('calving_date')->nullable();
            $table->enum('status', [
                'served',
                'pregnancy_check_due',
                'pregnant',
                'not_pregnant',
                'repeat_heat',
                'aborted',
                'calved',
            ])->default('served');
            $table->foreignId('calf_animal_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->index(['farmer_id', 'animal_id']);
            $table->index(['animal_id', 'is_current']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_pregnancies');
    }
};
