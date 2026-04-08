<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Reproductive\ReproductiveRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReproductiveController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'animal_id' => 'required|exists:animals,id',
            'lactation_number' => 'nullable|integer|min:0',
            'ai_date' => 'nullable|date',
            'breed_name' => 'nullable|string|max:255',
            'pregnancy_confirmation' => 'nullable|boolean',
            'calving_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $record = ReproductiveRecord::create($request->only([
            'animal_id',
            'lactation_number',
            'ai_date',
            'breed_name',
            'pregnancy_confirmation',
            'calving_date',
            'notes',
        ]));

        return response()->json([
            'status' => true,
            'message' => 'Reproductive record saved successfully',
            'data' => $record,
        ], 201);
    }

    public function index($farmer_id)
    {
        $records = ReproductiveRecord::with(['animal.farmer'])
            ->whereHas('animal', fn ($query) => $query->where('farmer_id', $farmer_id))
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Reproductive records fetched successfully',
            'data' => $records,
        ]);
    }
}
