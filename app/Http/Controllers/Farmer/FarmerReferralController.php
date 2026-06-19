<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Farmer;
use Illuminate\Http\Request;

class FarmerReferralController extends Controller
{
    public function index(Request $request)
    {
        $referredFarmers = Farmer::query()
            ->with([
                'referredByFarmer:id,first_name,last_name,mobile,referral_code,referral_points',
                'subscription.plan',
            ])
            ->whereNotNull('referred_by_farmer_id')
            ->latest()
            ->paginate($this->tablePerPage($request))
            ->withQueryString();

        $summary = [
            'total_referred' => Farmer::query()->whereNotNull('referred_by_farmer_id')->count(),
            'active_subscription' => Farmer::query()
                ->whereNotNull('referred_by_farmer_id')
                ->whereHas('subscription', fn ($query) => $query->where('status', 'active'))
                ->count(),
            'reward_pending' => Farmer::query()
                ->whereNotNull('referred_by_farmer_id')
                ->whereNull('farmer_referral_reward_granted_at')
                ->count(),
            'total_points_distributed' => (int) Farmer::query()->sum('referral_points'),
        ];

        return view('farmer.referred', compact('referredFarmers', 'summary'));
    }
}
