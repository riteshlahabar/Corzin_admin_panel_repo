<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            if (! Schema::hasColumn('farmers', 'referred_by_doctor_id')) {
                $table->foreignId('referred_by_doctor_id')
                    ->nullable()
                    ->after('mobile')
                    ->constrained('doctors')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('farmers', 'doctor_referral_code')) {
                $table->string('doctor_referral_code', 40)->nullable()->after('referred_by_doctor_id');
            }
            if (! Schema::hasColumn('farmers', 'referral_reward_granted_at')) {
                $table->timestamp('referral_reward_granted_at')->nullable()->after('doctor_referral_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            if (Schema::hasColumn('farmers', 'referral_reward_granted_at')) {
                $table->dropColumn('referral_reward_granted_at');
            }
            if (Schema::hasColumn('farmers', 'doctor_referral_code')) {
                $table->dropColumn('doctor_referral_code');
            }
            if (Schema::hasColumn('farmers', 'referred_by_doctor_id')) {
                $table->dropConstrainedForeignId('referred_by_doctor_id');
            }
        });
    }
};
