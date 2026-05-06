<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (! Schema::hasColumn('doctors', 'whatsapp_number')) {
                $table->string('whatsapp_number', 30)->nullable()->after('contact_number');
            }
        });

        DB::statement("ALTER TABLE doctors MODIFY clinic_name VARCHAR(255) NULL");
    }

    public function down(): void
    {
        DB::table('doctors')
            ->whereNull('clinic_name')
            ->update(['clinic_name' => '']);

        DB::statement("ALTER TABLE doctors MODIFY clinic_name VARCHAR(255) NOT NULL");

        Schema::table('doctors', function (Blueprint $table) {
            if (Schema::hasColumn('doctors', 'whatsapp_number')) {
                $table->dropColumn('whatsapp_number');
            }
        });
    }
};
