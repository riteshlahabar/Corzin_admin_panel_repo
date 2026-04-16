<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            if (! Schema::hasColumn('animals', 'is_for_sale')) {
                $table->boolean('is_for_sale')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('animals', 'listed_for_sale_at')) {
                $table->timestamp('listed_for_sale_at')->nullable()->after('is_for_sale');
            }
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            if (Schema::hasColumn('animals', 'listed_for_sale_at')) {
                $table->dropColumn('listed_for_sale_at');
            }
            if (Schema::hasColumn('animals', 'is_for_sale')) {
                $table->dropColumn('is_for_sale');
            }
        });
    }
};

