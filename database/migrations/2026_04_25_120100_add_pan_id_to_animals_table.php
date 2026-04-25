<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            if (! Schema::hasColumn('animals', 'pan_id')) {
                $table->foreignId('pan_id')->nullable()->after('animal_type_id')->constrained('farmer_pans')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            if (Schema::hasColumn('animals', 'pan_id')) {
                $table->dropConstrainedForeignId('pan_id');
            }
        });
    }
};

