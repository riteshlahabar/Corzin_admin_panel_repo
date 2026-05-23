<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\AnimalPregnancy;

class PregnancyListController extends Controller
{
    public function index()
    {
        $records = AnimalPregnancy::with(['farmer', 'animal.animalType', 'calfAnimal'])
            ->latest('is_current')
            ->latest('pregnancy_no')
            ->latest('service_no')
            ->latest('ai_date')
            ->get();

        $summary = [
            'total' => $records->count(),
            'current' => $records->where('is_current', true)->count(),
            'pregnant' => $records->where('status', 'pregnant')->count(),
            'calved' => $records->where('status', 'calved')->count(),
        ];

        return view('pregnancy.index', compact('records', 'summary'));
    }
}
