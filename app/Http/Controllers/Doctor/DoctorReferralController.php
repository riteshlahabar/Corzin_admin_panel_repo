<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Doctor\Doctor;

class DoctorReferralController extends Controller
{
    public function index()
    {
        $referredDoctors = Doctor::query()
            ->with('referredBy:id,first_name,last_name,contact_number,referral_code')
            ->whereNotNull('referred_by_doctor_id')
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $summary = [
            'total_referred' => Doctor::query()->whereNotNull('referred_by_doctor_id')->count(),
            'approved_referred' => Doctor::query()
                ->whereNotNull('referred_by_doctor_id')
                ->where('status', 'approved')
                ->count(),
            'pending_referred' => Doctor::query()
                ->whereNotNull('referred_by_doctor_id')
                ->where('status', 'pending')
                ->count(),
            'total_points_distributed' => (int) Doctor::query()->sum('referral_points'),
        ];

        return view('doctor.referred', compact('referredDoctors', 'summary'));
    }
}

