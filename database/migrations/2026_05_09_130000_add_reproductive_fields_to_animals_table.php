<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            if (! Schema::hasColumn('animals', 'lactation_number')) {
                $table->unsignedInteger('lactation_number')->nullable();
            }

            if (! Schema::hasColumn('animals', 'ai_date')) {
                $table->date('ai_date')->nullable();
            }

            if (! Schema::hasColumn('animals', 'breed_name')) {
                $table->string('breed_name')->nullable();
            }
        });

        if (Schema::hasTable('reproductive_records')) {
            DB::table('reproductive_records')
                ->whereNotNull('animal_id')
                ->orderByDesc('id')
                ->get()
                ->unique('animal_id')
                ->each(function ($record) {
                    DB::table('animals')
                        ->where('id', $record->animal_id)
                        ->update([
                            'lactation_number' => $record->lactation_number,
                            'ai_date' => $record->ai_date,
                            'breed_name' => $record->breed_name,
                        ]);
                });
        }
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            if (Schema::hasColumn('animals', 'breed_name')) {
                $table->dropColumn('breed_name');
            }

            if (Schema::hasColumn('animals', 'ai_date')) {
                $table->dropColumn('ai_date');
            }

            if (Schema::hasColumn('animals', 'lactation_number')) {
                $table->dropColumn('lactation_number');
            }
        });
    }
};
