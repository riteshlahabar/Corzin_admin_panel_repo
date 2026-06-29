<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Animal;
use App\Models\Farmer\FarmerPan;
use App\Models\Farmer\FeedDietPlan;
use App\Models\Farmer\Farmer;
use App\Models\Farmer\FeedingRecord;
use App\Models\Farmer\FeedType;
use Illuminate\Http\Request;

class FeedingListController extends Controller
{
    public function index()
    {
        $records = FeedingRecord::with(['farmer', 'animal', 'feedType', 'dietPlan.pan'])->latest('date')->latest('id')->get();
        $farmers = Farmer::orderBy('first_name')->get();
        $animals = Animal::with('farmer')->where('is_active', true)->latest()->get();
        $pans = FarmerPan::with('farmer')->latest()->get();
        $dietPlans = FeedDietPlan::with(['farmer', 'animal', 'pan', 'feedType'])
            ->where('is_active', true)
            ->latest('id')
            ->get();
        $feedTypes = FeedType::where('is_active', true)->orderBy('name')->get();

        $summary = [
            'total' => $records->count(),
            'today' => $records->where('date', now()->toDateString())->sum('quantity'),
            'types' => $feedTypes->count(),
        ];

        return view('feeding.index', compact('records', 'farmers', 'animals', 'pans', 'dietPlans', 'feedTypes', 'summary'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'animal_id' => 'nullable|exists:animals,id',
            'pan_id' => 'nullable|exists:farmer_pans,id',
            'diet_plan_id' => 'required|exists:feed_diet_plans,id',
            'quantity' => 'required|numeric|min:0.01',
            'feeding_time' => 'required|in:Morning,Afternoon,Evening',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $farmerId = (int) $data['farmer_id'];
        $animalId = !empty($data['animal_id']) ? (int) $data['animal_id'] : null;
        $panId = !empty($data['pan_id']) ? (int) $data['pan_id'] : null;

        if (($animalId && $panId) || (! $animalId && ! $panId)) {
            return redirect()
                ->route('farmer.feeding')
                ->with('error', 'Please select either Animal or Pen.')
                ->withInput();
        }

        $dietPlan = FeedDietPlan::query()
            ->where('id', (int) $data['diet_plan_id'])
            ->where('farmer_id', $farmerId)
            ->where('is_active', true)
            ->first();

        if (! $dietPlan) {
            return redirect()
                ->route('farmer.feeding')
                ->with('error', 'Selected diet plan is not valid for this farmer.')
                ->withInput();
        }

        if ($animalId && (int) ($dietPlan->animal_id ?? 0) !== $animalId) {
            return redirect()
                ->route('farmer.feeding')
                ->with('error', 'Selected diet plan does not belong to the chosen animal.')
                ->withInput();
        }

        if ($panId && (int) ($dietPlan->pan_id ?? 0) !== $panId) {
            return redirect()
                ->route('farmer.feeding')
                ->with('error', 'Selected diet plan does not belong to the chosen pen.')
                ->withInput();
        }

        $feedingQuantity = round((float) $data['quantity'], 2);
        $packageQuantity = round((float) ($dietPlan->plan_quantity ?? 0), 2);
        $balanceQuantity = max(round($packageQuantity - $feedingQuantity, 2), 0);

        FeedingRecord::create([
            'farmer_id' => $farmerId,
            'animal_id' => $animalId ?: (int) $dietPlan->animal_id,
            'feed_type_id' => (int) $dietPlan->feed_type_id,
            'diet_plan_id' => (int) $dietPlan->id,
            'feed_subtype_details' => $dietPlan->subtype_details ?? [],
            'quantity' => $feedingQuantity,
            'package_quantity' => $packageQuantity,
            'feeding_quantity' => $feedingQuantity,
            'balance_quantity' => $balanceQuantity,
            'rate_per_unit' => 0,
            'feeding_cost' => 0,
            'unit' => $dietPlan->unit ?: 'Kg',
            'feeding_time' => $data['feeding_time'],
            'date' => $data['date'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('farmer.feeding')->with('success', 'Feeding entry saved successfully.');
    }
}
