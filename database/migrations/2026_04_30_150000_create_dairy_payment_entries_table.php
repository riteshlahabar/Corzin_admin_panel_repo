<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dairy_payment_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('farmer_id');
            $table->unsignedBigInteger('dairy_id');
            $table->date('payment_date');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['farmer_id', 'dairy_id', 'payment_date'], 'dairy_payment_entries_unique_day');
            $table->index(['dairy_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dairy_payment_entries');
    }
};

