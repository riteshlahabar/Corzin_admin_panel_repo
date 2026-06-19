<?php

use App\Models\Farmer\Farmer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            if (! Schema::hasColumn('farmers', 'referral_code')) {
                $table->string('referral_code', 40)->nullable()->after('mobile');
            }
            if (! Schema::hasColumn('farmers', 'referral_points')) {
                $table->unsignedInteger('referral_points')->default(0)->after('referral_code');
            }
            if (! Schema::hasColumn('farmers', 'referred_by_farmer_id')) {
                $table->foreignId('referred_by_farmer_id')
                    ->nullable()
                    ->after('referral_reward_granted_at')
                    ->constrained('farmers')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('farmers', 'farmer_referral_code')) {
                $table->string('farmer_referral_code', 40)->nullable()->after('referred_by_farmer_id');
            }
            if (! Schema::hasColumn('farmers', 'farmer_referral_reward_granted_at')) {
                $table->timestamp('farmer_referral_reward_granted_at')->nullable()->after('farmer_referral_code');
            }
        });

        Farmer::query()
            ->select(['id', 'referral_code'])
            ->where(function ($query) {
                $query->whereNull('referral_code')
                    ->orWhere('referral_code', '');
            })
            ->chunkById(100, function ($farmers): void {
                foreach ($farmers as $farmer) {
                    DB::table('farmers')
                        ->where('id', $farmer->id)
                        ->update(['referral_code' => Farmer::generateUniqueReferralCode()]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            if (Schema::hasColumn('farmers', 'farmer_referral_reward_granted_at')) {
                $table->dropColumn('farmer_referral_reward_granted_at');
            }
            if (Schema::hasColumn('farmers', 'farmer_referral_code')) {
                $table->dropColumn('farmer_referral_code');
            }
            if (Schema::hasColumn('farmers', 'referred_by_farmer_id')) {
                $table->dropConstrainedForeignId('referred_by_farmer_id');
            }
            if (Schema::hasColumn('farmers', 'referral_points')) {
                $table->dropColumn('referral_points');
            }
            if (Schema::hasColumn('farmers', 'referral_code')) {
                $table->dropColumn('referral_code');
            }
        });
    }
};
