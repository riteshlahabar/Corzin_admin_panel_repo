<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\MilkProduction;

class MilkProduceListController extends Controller
{
    public function index()
    {
        $milkProductions = MilkProduction::with(['animal.farmer', 'dairy'])->latest()->get();

        $summary = [
            'morning' => MilkProduction::sum('morning_milk'),
            'afternoon' => MilkProduction::sum('afternoon_milk'),
            'evening' => MilkProduction::sum('evening_milk'),
            'fat' => MilkProduction::avg('fat'),
        ];

        return view('milk_production.index', compact('milkProductions', 'summary'));
    }
}
