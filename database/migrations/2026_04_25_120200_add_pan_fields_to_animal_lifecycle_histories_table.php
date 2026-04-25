<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animal_lifecycle_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('animal_lifecycle_histories', 'from_pan_id')) {
                $table->foreignId('from_pan_id')->nullable()->after('to_animal_type_id')->constrained('farmer_pans')->nullOnDelete();
            }
            if (! Schema::hasColumn('animal_lifecycle_histories', 'to_pan_id')) {
                $table->foreignId('to_pan_id')->nullable()->after('from_pan_id')->constrained('farmer_pans')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('animal_lifecycle_histories', function (Blueprint $table) {
            if (Schema::hasColumn('animal_lifecycle_histories', 'to_pan_id')) {
                $table->dropConstrainedForeignId('to_pan_id');
            }
            if (Schema::hasColumn('animal_lifecycle_histories', 'from_pan_id')) {
                $table->dropConstrainedForeignId('from_pan_id');
            }
        });
    }
};

