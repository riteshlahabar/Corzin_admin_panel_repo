<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_appointment_id')->nullable();
            $table->string('event')->nullable();
            $table->string('title');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_admin_notifications');
    }
};
