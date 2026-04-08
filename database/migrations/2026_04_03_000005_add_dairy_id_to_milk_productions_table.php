<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('milk_productions', function (Blueprint $table) {
            $table->foreignId('dairy_id')->nullable()->after('animal_id')->constrained('dairies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('milk_productions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dairy_id');
        });
    }
};
