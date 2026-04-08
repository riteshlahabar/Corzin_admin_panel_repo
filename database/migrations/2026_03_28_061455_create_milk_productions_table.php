<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('milk_productions', function (Blueprint $table) {
        $table->id();

        $table->foreignId('animal_id')->constrained()->onDelete('cascade');

        $table->date('date');

        $table->float('morning_milk')->default(0);
        $table->float('afternoon_milk')->default(0);
        $table->float('evening_milk')->default(0);
        $table->float('total_milk')->default(0);

        $table->float('fat')->nullable();
        $table->float('snf')->nullable();

        $table->float('rate')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milk_productions');
    }
};
