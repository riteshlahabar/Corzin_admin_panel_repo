<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mastitis_records', function (Blueprint $table) {
            if (! Schema::hasColumn('mastitis_records', 'case_id')) {
                $table->unsignedBigInteger('case_id')->nullable()->after('id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('mastitis_records', function (Blueprint $table) {
            if (Schema::hasColumn('mastitis_records', 'case_id')) {
                $table->dropColumn('case_id');
            }
        });
    }
};