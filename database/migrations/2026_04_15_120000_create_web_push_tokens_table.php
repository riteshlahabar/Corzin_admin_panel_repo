<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('web_push_tokens', function (Blueprint $table) {
            $table->id();
            $table->text('token');
            $table->string('device_name')->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['token'], 'web_push_tokens_token_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_push_tokens');
    }
};

