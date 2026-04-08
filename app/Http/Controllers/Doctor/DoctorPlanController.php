<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Doctor\DoctorPlan;
use Illuminate\Http\Request;

class DoctorPlanController extends Controller
{
    public function index()
    {
        $plans = DoctorPlan::query()->latest('id')->get();

        return view('doctor.plan', compact('plans'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'features' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DoctorPlan::create([
            'name' => $data['name'],
            'price' => $data['price'],
            'duration_days' => $data['duration_days'],
            'features' => $data['features'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('doctor.plan.index')->with('success', 'Doctor plan saved successfully.');
    }

    public function update(Request $request, DoctorPlan $doctorPlan)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'features' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $doctorPlan->update([
            'name' => $data['name'],
            'price' => $data['price'],
            'duration_days' => $data['duration_days'],
            'features' => $data['features'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('doctor.plan.index')->with('success', 'Doctor plan updated successfully.');
    }
}

