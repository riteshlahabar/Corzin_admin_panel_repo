<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Farmer;
use App\Models\Farmer\FarmerPlan;
use App\Models\Farmer\FarmerSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FarmerSubscriptionController extends Controller
{
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

        $farmers = $farmersQuery->paginate(20)->withQueryString();
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

        FarmerSubscription::updateOrCreate(
            ['farmer_id' => $data['farmer_id']],
            [
                'farmer_plan_id' => $data['farmer_plan_id'],
                'start_date' => $startDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'status' => $status,
                'notes' => $data['notes'] ?? null,
            ]
        );

        return redirect()->route('farmer.subscription.index')->with('success', 'Farmer subscription updated successfully.');
    }
}
