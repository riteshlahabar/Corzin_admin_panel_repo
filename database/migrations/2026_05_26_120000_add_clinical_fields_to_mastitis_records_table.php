<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('mastitis_records', function (Blueprint $table) {
            if (! Schema::hasColumn('mastitis_records', 'quarter')) {
                $table->string('quarter', 50)->nullable()->after('recovery_status');
            }
            if (! Schema::hasColumn('mastitis_records', 'clinical_type')) {
                $table->string('clinical_type', 50)->nullable()->after('quarter');
            }
            if (! Schema::hasColumn('mastitis_records', 'cmt_score')) {
                $table->string('cmt_score', 20)->nullable()->after('clinical_type');
            }
            if (! Schema::hasColumn('mastitis_records', 'scc_count')) {
                $table->decimal('scc_count', 12, 2)->nullable()->after('cmt_score');
            }
            if (! Schema::hasColumn('mastitis_records', 'follow_up_date')) {
                $table->date('follow_up_date')->nullable()->after('date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mastitis_records', function (Blueprint $table) {
            $drop = [];
            foreach (['quarter', 'clinical_type', 'cmt_score', 'scc_count', 'follow_up_date'] as $column) {
                if (Schema::hasColumn('mastitis_records', $column)) {
                    $drop[] = $column;
                }
            }
            if (! empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};

