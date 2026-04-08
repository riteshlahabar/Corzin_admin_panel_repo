<?php

namespace App\Http\Controllers\Reproductive;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Animal;
use App\Models\Reproductive\ReproductiveRecord;
use Illuminate\Http\Request;

class ReproductiveListController extends Controller
{
    public function index()
    {
        $records = ReproductiveRecord::with(['animal.farmer'])->latest()->get();
        $animals = Animal::with('farmer')->orderBy('animal_name')->get();

        $summary = [
            'total' => $records->count(),
            'pregnant' => $records->where('pregnancy_confirmation', true)->count(),
            'calving' => $records->whereNotNull('calving_date')->count(),
        ];

        return view('reproductive.index', compact('records', 'animals', 'summary'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'animal_id' => 'required|exists:animals,id',
            'lactation_number' => 'nullable|integer|min:0',
            'ai_date' => 'nullable|date',
            'breed_name' => 'nullable|string|max:255',
            'pregnancy_confirmation' => 'nullable|boolean',
            'calving_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        ReproductiveRecord::create([
            ...$data,
            'pregnancy_confirmation' => $request->boolean('pregnancy_confirmation'),
        ]);

        return redirect()->route('reproductive.index')->with('success', 'Reproductive record saved successfully.');
    }
}
