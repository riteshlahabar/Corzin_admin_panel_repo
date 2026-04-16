<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            if (! Schema::hasColumn('animals', 'mother_animal_id')) {
                $table->foreignId('mother_animal_id')
                    ->nullable()
                    ->after('animal_type_id')
                    ->constrained('animals')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            if (Schema::hasColumn('animals', 'mother_animal_id')) {
                $table->dropConstrainedForeignId('mother_animal_id');
            }
        });
    }
};

