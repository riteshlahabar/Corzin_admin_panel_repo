<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\MilkProduction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MilkProductionController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'animal_id' => 'required|exists:animals,id',
            'dairy_id' => 'nullable|exists:dairies,id',
            'date' => 'required|date_format:Y-m-d',
            'shift' => 'required|in:Morning,Afternoon,Evening',
            'quantity' => 'required|numeric|min:0.1',
            'fat' => 'nullable|numeric|min:0',
            'snf' => 'nullable|numeric|min:0',
            'rate' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $date = Carbon::createFromFormat('Y-m-d', $request->date)->format('Y-m-d');
        $quantity = (float) $request->quantity;
        $morning = 0;
        $afternoon = 0;
        $evening = 0;

        if ($request->shift === 'Morning') {
            $morning = $quantity;
        } elseif ($request->shift === 'Afternoon') {
            $afternoon = $quantity;
        } else {
            $evening = $quantity;
        }

        $milk = MilkProduction::create([
            'animal_id' => $request->animal_id,
            'dairy_id' => $request->dairy_id,
            'date' => $date,
            'morning_milk' => $morning,
            'afternoon_milk' => $afternoon,
            'evening_milk' => $evening,
            'fat' => $request->fat,
            'snf' => $request->snf,
            'rate' => $request->rate,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Milk entry added successfully',
            'data' => [
                'id' => $milk->id,
                'animal_id' => $milk->animal_id,
                'dairy_id' => $milk->dairy_id,
                'date' => Carbon::parse($milk->date)->format('d/m/Y'),
                'morning_milk' => $milk->morning_milk,
                'afternoon_milk' => $milk->afternoon_milk,
                'evening_milk' => $milk->evening_milk,
                'total_milk' => $milk->total_milk,
                'fat' => $milk->fat,
                'snf' => $milk->snf,
                'rate' => $milk->rate,
            ]
        ]);
    }

    public function index($animal_id)
    {
        $data = MilkProduction::with('dairy')
            ->where('animal_id', $animal_id)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'date' => Carbon::parse($item->date)->format('d/m/Y'),
                    'dairy_name' => $item->dairy->dairy_name ?? null,
                    'morning_milk' => $item->morning_milk,
                    'afternoon_milk' => $item->afternoon_milk,
                    'evening_milk' => $item->evening_milk,
                    'total_milk' => $item->total_milk,
                    'fat' => $item->fat,
                    'snf' => $item->snf,
                    'rate' => $item->rate,
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function listByFarmer($farmer_id)
    {
        $data = MilkProduction::with(['dairy', 'animal'])
            ->whereHas('animal', function ($query) use ($farmer_id) {
                $query->where('farmer_id', $farmer_id);
            })
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'animal_id' => $item->animal_id,
                    'animal_name' => $item->animal->animal_name ?? null,
                    'tag_number' => $item->animal->tag_number ?? null,
                    'dairy_id' => $item->dairy_id,
                    'dairy_name' => $item->dairy->dairy_name ?? null,
                    'date' => Carbon::parse($item->date)->format('d/m/Y'),
                    'morning_milk' => $item->morning_milk,
                    'afternoon_milk' => $item->afternoon_milk,
                    'evening_milk' => $item->evening_milk,
                    'total_milk' => $item->total_milk,
                    'fat' => $item->fat,
                    'snf' => $item->snf,
                    'rate' => $item->rate,
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function update(Request $request, $milk_id)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'dairy_id' => 'nullable|exists:dairies,id',
            'date' => 'required|date_format:Y-m-d',
            'shift' => 'required|in:Morning,Afternoon,Evening',
            'quantity' => 'required|numeric|min:0',
            'fat' => 'nullable|numeric|min:0',
            'snf' => 'nullable|numeric|min:0',
            'rate' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $milk = MilkProduction::with('animal')
            ->where('id', $milk_id)
            ->first();

        if (! $milk || ! $milk->animal || (int) $milk->animal->farmer_id !== (int) $request->farmer_id) {
            return response()->json([
                'status' => false,
                'message' => 'Milk entry not found for this farmer.',
            ], 404);
        }

        $quantity = (float) $request->quantity;
        $morning = (float) ($milk->morning_milk ?? 0);
        $afternoon = (float) ($milk->afternoon_milk ?? 0);
        $evening = (float) ($milk->evening_milk ?? 0);

        if ($request->shift === 'Morning') {
            $morning = $quantity;
        } elseif ($request->shift === 'Afternoon') {
            $afternoon = $quantity;
        } else {
            $evening = $quantity;
        }

        $milk->update([
            'dairy_id' => $request->dairy_id,
            'date' => Carbon::createFromFormat('Y-m-d', $request->date)->format('Y-m-d'),
            'morning_milk' => $morning,
            'afternoon_milk' => $afternoon,
            'evening_milk' => $evening,
            'fat' => $request->fat,
            'snf' => $request->snf,
            'rate' => $request->rate,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Milk entry updated successfully',
            'data' => [
                'id' => $milk->id,
                'animal_id' => $milk->animal_id,
                'dairy_id' => $milk->dairy_id,
                'date' => Carbon::parse($milk->date)->format('d/m/Y'),
                'morning_milk' => $milk->morning_milk,
                'afternoon_milk' => $milk->afternoon_milk,
                'evening_milk' => $milk->evening_milk,
                'total_milk' => $milk->total_milk,
                'fat' => $milk->fat,
                'snf' => $milk->snf,
                'rate' => $milk->rate,
            ],
        ]);
    }
}
