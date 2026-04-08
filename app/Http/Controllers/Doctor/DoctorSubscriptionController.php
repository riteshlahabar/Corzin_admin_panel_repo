<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Doctor\Doctor;
use App\Models\Doctor\DoctorPlan;
use App\Models\Doctor\DoctorSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DoctorSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $doctorsQuery = Doctor::query()
            ->with(['subscription.plan'])
            ->latest('id');

        if ($request->filled('search')) {
            $search = strtolower(trim((string) $request->search));
            $doctorsQuery->where(function ($builder) use ($search) {
                $builder->whereRaw('LOWER(first_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(contact_number) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        $doctors = $doctorsQuery->paginate(20)->withQueryString();
        $doctorOptions = Doctor::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'contact_number']);
        $plans = DoctorPlan::query()->where('is_active', true)->orderBy('name')->get();

        $summaryQuery = DoctorSubscription::query();
        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'active' => (clone $summaryQuery)->where('status', 'active')->count(),
            'expiring_soon' => (clone $summaryQuery)
                ->whereDate('due_date', '>=', now()->toDateString())
                ->whereDate('due_date', '<=', now()->addDays(7)->toDateString())
                ->count(),
            'expired' => (clone $summaryQuery)->whereDate('due_date', '<', now()->toDateString())->count(),
        ];

        return view('doctor.subscription', compact('doctors', 'doctorOptions', 'plans', 'summary'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'doctor_id' => ['required', 'exists:doctors,id'],
            'doctor_plan_id' => ['required', 'exists:doctor_plans,id'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:active,expired,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        $plan = DoctorPlan::findOrFail($data['doctor_plan_id']);
        $startDate = isset($data['start_date']) ? Carbon::parse($data['start_date']) : now();
        $dueDate = isset($data['due_date'])
            ? Carbon::parse($data['due_date'])
            : (clone $startDate)->addDays((int) $plan->duration_days);
        $status = $data['status'] ?? ($dueDate->isPast() ? 'expired' : 'active');

        DoctorSubscription::updateOrCreate(
            ['doctor_id' => $data['doctor_id']],
            [
                'doctor_plan_id' => $data['doctor_plan_id'],
                'start_date' => $startDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'status' => $status,
                'notes' => $data['notes'] ?? null,
            ]
        );

        return redirect()->route('doctor.subscription.index')->with('success', 'Doctor subscription updated successfully.');
    }
}
