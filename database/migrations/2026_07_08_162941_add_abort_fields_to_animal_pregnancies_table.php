<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('animal_pregnancies', function (Blueprint $table) {
            $table->date('abort_date')->nullable()->after('calving_date');
            $table->text('abort_reason')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('animal_pregnancies', function (Blueprint $table) {
            $table->dropColumn(['abort_date', 'abort_reason']);
        });
    }
};