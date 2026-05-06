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
            if (! Schema::hasColumn('doctors', 'referral_code')) {
                $table->string('referral_code', 40)->nullable()->unique()->after('email');
            }
            if (! Schema::hasColumn('doctors', 'referred_by_doctor_id')) {
                $table->foreignId('referred_by_doctor_id')
                    ->nullable()
                    ->after('referral_code')
                    ->constrained('doctors')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('doctors', 'referral_points')) {
                $table->unsignedInteger('referral_points')->default(0)->after('referred_by_doctor_id');
            }
            if (! Schema::hasColumn('doctors', 'referral_reward_granted_at')) {
                $table->timestamp('referral_reward_granted_at')->nullable()->after('referral_points');
            }
        });

        $doctors = DB::table('doctors')
            ->select('id', 'referral_code')
            ->orderBy('id')
            ->get();

        $usedCodes = [];
        foreach ($doctors as $doctor) {
            $current = strtoupper(trim((string) $doctor->referral_code));

            if ($current !== '' && ! isset($usedCodes[$current])) {
                $usedCodes[$current] = true;
                continue;
            }

            $base = 'DOC'.str_pad((string) $doctor->id, 6, '0', STR_PAD_LEFT);
            $candidate = $base;
            $suffix = 1;

            while (isset($usedCodes[$candidate])) {
                $candidate = $base.$suffix;
                $suffix++;
            }

            DB::table('doctors')
                ->where('id', $doctor->id)
                ->update(['referral_code' => $candidate]);

            $usedCodes[$candidate] = true;
        }
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (Schema::hasColumn('doctors', 'referred_by_doctor_id')) {
                $table->dropConstrainedForeignId('referred_by_doctor_id');
            }
            if (Schema::hasColumn('doctors', 'referral_reward_granted_at')) {
                $table->dropColumn('referral_reward_granted_at');
            }
            if (Schema::hasColumn('doctors', 'referral_points')) {
                $table->dropColumn('referral_points');
            }
            if (Schema::hasColumn('doctors', 'referral_code')) {
                $table->dropUnique('doctors_referral_code_unique');
                $table->dropColumn('referral_code');
            }
        });
    }
};

