<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Doctor\Doctor;
use App\Models\Farmer\Farmer;
use Illuminate\Http\Request;

class DoctorReferralController extends Controller
{
    public function index(Request $request)
    {
        $referredFarmers = Farmer::query()
            ->with([
                'referredByDoctor:id,first_name,last_name,contact_number,referral_code,referral_points',
                'subscription.plan',
            ])
            ->whereNotNull('referred_by_doctor_id')
            ->latest()
            ->paginate($this->tablePerPage($request))
            ->withQueryString();

        $summary = [
            'total_referred' => Farmer::query()->whereNotNull('referred_by_doctor_id')->count(),
            'active_subscription' => Farmer::query()
                ->whereNotNull('referred_by_doctor_id')
                ->whereHas('subscription', fn ($query) => $query->where('status', 'active'))
                ->count(),
            'reward_pending' => Farmer::query()
                ->whereNotNull('referred_by_doctor_id')
                ->whereNull('referral_reward_granted_at')
                ->count(),
            'total_points_distributed' => (int) Doctor::query()->sum('referral_points'),
        ];

        return view('doctor.referred', compact('referredFarmers', 'summary'));
    }
}
