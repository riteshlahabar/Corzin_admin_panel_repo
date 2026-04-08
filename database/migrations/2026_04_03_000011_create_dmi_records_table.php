<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dmi_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('farmers')->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->decimal('body_weight', 10, 2);
            $table->decimal('total_milk', 10, 2);
            $table->decimal('required_dmi', 10, 2);
            $table->decimal('actual_dmi', 10, 2);
            $table->string('alert_status');
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dmi_records');
    }
};
