<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Animal;
use App\Models\Farmer\Farmer;
use App\Models\Farmer\FeedingRecord;
use App\Models\Farmer\FeedType;
use Illuminate\Http\Request;

class FeedingListController extends Controller
{
    public function index()
    {
        $records = FeedingRecord::with(['farmer', 'animal', 'feedType'])->latest('date')->latest('id')->get();
        $farmers = Farmer::orderBy('first_name')->get();
        $animals = Animal::with('farmer')->where('is_active', true)->latest()->get();
        $feedTypes = FeedType::where('is_active', true)->orderBy('name')->get();

        $summary = [
            'total' => $records->count(),
            'today' => $records->where('date', now()->toDateString())->sum('quantity'),
            'types' => $feedTypes->count(),
        ];

        return view('feeding.index', compact('records', 'farmers', 'animals', 'feedTypes', 'summary'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'animal_id' => 'required|exists:animals,id',
            'feed_type_id' => 'required|exists:feed_types,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit' => 'required|in:Kg,Gram',
            'feeding_time' => 'required|in:Morning,Afternoon,Evening',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        FeedingRecord::create($data);

        return redirect()->route('farmer.feeding')->with('success', 'Feeding entry saved successfully.');
    }
}
