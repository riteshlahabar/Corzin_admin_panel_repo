<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_subtypes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('feed_type_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('feed_type_id')
                ->references('id')
                ->on('feed_types')
                ->cascadeOnDelete();

            $table->unique(['feed_type_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_subtypes');
    }
};

