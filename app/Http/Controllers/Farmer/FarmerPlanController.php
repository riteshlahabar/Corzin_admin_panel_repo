<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\FarmerPlan;
use Illuminate\Http\Request;

class FarmerPlanController extends Controller
{
    public function index()
    {
        $plans = FarmerPlan::query()->latest('id')->get();

        return view('farmer.plan', compact('plans'));
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

        FarmerPlan::create([
            'name' => $data['name'],
            'price' => $data['price'],
            'duration_days' => $data['duration_days'],
            'features' => $data['features'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('farmer.plan.index')->with('success', 'Farmer plan saved successfully.');
    }

    public function update(Request $request, FarmerPlan $farmerPlan)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'features' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $farmerPlan->update([
            'name' => $data['name'],
            'price' => $data['price'],
            'duration_days' => $data['duration_days'],
            'features' => $data['features'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('farmer.plan.index')->with('success', 'Farmer plan updated successfully.');
    }
}

