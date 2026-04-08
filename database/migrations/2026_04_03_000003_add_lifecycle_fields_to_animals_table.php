<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->string('lifecycle_status')->default('active')->after('image');
            $table->boolean('is_active')->default(true)->after('lifecycle_status');
            $table->timestamp('sold_at')->nullable()->after('is_active');
            $table->timestamp('death_at')->nullable()->after('sold_at');
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn(['lifecycle_status', 'is_active', 'sold_at', 'death_at']);
        });
    }
};
