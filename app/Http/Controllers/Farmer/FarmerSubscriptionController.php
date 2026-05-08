<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Doctor\Doctor;
use App\Models\Farmer\Farmer;
use App\Models\Farmer\FarmerPlan;
use App\Models\Farmer\FarmerSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FarmerSubscriptionController extends Controller
{
    private const REFERRAL_REWARD_POINTS = 50;

    public function index(Request $request)
    {
        $farmersQuery = Farmer::query()
            ->with(['subscription.plan'])
            ->latest('id');

        if ($request->filled('search')) {
            $search = strtolower(trim((string) $request->search));
            $farmersQuery->where(function ($builder) use ($search) {
                $builder->whereRaw('LOWER(first_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(mobile) LIKE ?', ["%{$search}%"]);
            });
        }

        $farmers = $farmersQuery->paginate($this->tablePerPage($request))->withQueryString();
        $farmerOptions = Farmer::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'mobile']);
        $plans = FarmerPlan::query()->where('is_active', true)->orderBy('name')->get();

        $summaryQuery = FarmerSubscription::query();
        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'active' => (clone $summaryQuery)->where('status', 'active')->count(),
            'expiring_soon' => (clone $summaryQuery)
                ->whereDate('due_date', '>=', now()->toDateString())
                ->whereDate('due_date', '<=', now()->addDays(7)->toDateString())
                ->count(),
            'expired' => (clone $summaryQuery)->whereDate('due_date', '<', now()->toDateString())->count(),
        ];

        return view('farmer.subscription', compact('farmers', 'farmerOptions', 'plans', 'summary'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'farmer_id' => ['required', 'exists:farmers,id'],
            'farmer_plan_id' => ['required', 'exists:farmer_plans,id'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:active,expired,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        $plan = FarmerPlan::findOrFail($data['farmer_plan_id']);
        $startDate = isset($data['start_date']) ? Carbon::parse($data['start_date']) : now();
        $dueDate = isset($data['due_date'])
            ? Carbon::parse($data['due_date'])
            : (clone $startDate)->addDays((int) $plan->duration_days);
        $status = $data['status'] ?? ($dueDate->isPast() ? 'expired' : 'active');

        $subscription = FarmerSubscription::updateOrCreate(
            ['farmer_id' => $data['farmer_id']],
            [
                'farmer_plan_id' => $data['farmer_plan_id'],
                'start_date' => $startDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'status' => $status,
                'notes' => $data['notes'] ?? null,
            ]
        );

        $this->grantDoctorReferralRewardIfEligible($subscription->fresh('farmer'));

        return redirect()->route('farmer.subscription.index')->with('success', 'Farmer subscription updated successfully.');
    }

    private function grantDoctorReferralRewardIfEligible(?FarmerSubscription $subscription): void
    {
        if (! $subscription || strtolower((string) $subscription->status) !== 'active') {
            return;
        }

        $farmer = $subscription->farmer;
        if (! $farmer || ! $farmer->referred_by_doctor_id || $farmer->referral_reward_granted_at) {
            return;
        }

        if (! $farmer->created_at || $farmer->created_at->gt(now()->subMonth())) {
            return;
        }

        DB::transaction(function () use ($subscription): void {
            $lockedFarmer = Farmer::query()
                ->whereKey($subscription->farmer_id)
                ->lockForUpdate()
                ->first();

            if (! $lockedFarmer ||
                ! $lockedFarmer->referred_by_doctor_id ||
                $lockedFarmer->referral_reward_granted_at ||
                ! $lockedFarmer->created_at ||
                $lockedFarmer->created_at->gt(now()->subMonth())) {
                return;
            }

            $activeSubscription = FarmerSubscription::query()
                ->where('farmer_id', $lockedFarmer->id)
                ->where('status', 'active')
                ->exists();
            if (! $activeSubscription) {
                return;
            }

            Doctor::query()
                ->whereKey($lockedFarmer->referred_by_doctor_id)
                ->increment('referral_points', self::REFERRAL_REWARD_POINTS);

            $lockedFarmer->referral_reward_granted_at = now();
            $lockedFarmer->save();
        });
    }
}
