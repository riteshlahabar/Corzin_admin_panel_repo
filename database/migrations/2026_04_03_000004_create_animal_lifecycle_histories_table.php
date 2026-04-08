<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_lifecycle_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->string('action_type');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->foreignId('from_animal_type_id')->nullable()->constrained('animal_types')->nullOnDelete();
            $table->foreignId('to_animal_type_id')->nullable()->constrained('animal_types')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_lifecycle_histories');
    }
};
