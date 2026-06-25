<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\FeedDietPlan;

class DietPlanListController extends Controller
{
    public function index()
    {
        $plans = FeedDietPlan::query()
            ->with(['farmer', 'animal', 'pan', 'feedType'])
            ->latest('reference_date')
            ->latest('id')
            ->get();

        $summary = [
            'total' => $plans->count(),
            'active' => $plans->where('is_active', true)->count(),
            'planned_quantity' => round((float) $plans->sum(fn ($plan) => (float) ($plan->plan_quantity ?? 0)), 2),
        ];

        return view('feeding.diet_plans', compact('plans', 'summary'));
    }
}
