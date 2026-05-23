<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Animal;
use App\Models\Farmer\FarmerPan;
use App\Models\Farmer\MilkProduction;
use App\Models\Farmer\PanMilkEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $dateObject = Carbon::createFromFormat('Y-m-d', $request->date)->startOfDay();
        if ($dateObject->gt(now()->startOfDay())) {
            return response()->json([
                'status' => false,
                'message' => 'Milk date cannot be a future date.',
            ], 422);
        }
        $date = $dateObject->format('Y-m-d');
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

    public function storePan(Request $request)
    {
        if (! $request->filled('quantity_liters') && $request->filled('quantity')) {
            $request->merge(['quantity_liters' => $request->quantity]);
        }

        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'pan_id' => 'required|exists:farmer_pans,id',
            'dairy_id' => 'nullable|exists:dairies,id',
            'date' => 'required|date_format:Y-m-d',
            'shift' => 'required|in:Morning,Afternoon,Evening',
            'quantity_liters' => 'required|numeric|min:0.1',
            'fat' => 'nullable|numeric|min:0',
            'snf' => 'nullable|numeric|min:0',
            'rate' => 'nullable|numeric|min:0',
            'cow_milk_details' => 'required|array|min:1',
            'cow_milk_details.*.animal_id' => 'required|integer|exists:animals,id',
            'cow_milk_details.*.final_milk_qty' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $farmerId = (int) $request->farmer_id;
        $pan = FarmerPan::where('id', (int) $request->pan_id)
            ->where('farmer_id', $farmerId)
            ->first();

        if (! $pan) {
            return response()->json([
                'status' => false,
                'message' => 'PAN not found for this farmer.',
            ], 404);
        }

        $dateObject = Carbon::createFromFormat('Y-m-d', $request->date)->startOfDay();
        if ($dateObject->gt(now()->startOfDay())) {
            return response()->json([
                'status' => false,
                'message' => 'Milk date cannot be a future date.',
            ], 422);
        }

        $details = collect($request->input('cow_milk_details', []))
            ->map(function ($item) {
                return [
                    'animal_id' => (int) ($item['animal_id'] ?? 0),
                    'final_milk_qty' => round((float) ($item['final_milk_qty'] ?? 0), 2),
                ];
            })
            ->filter(fn ($item) => $item['animal_id'] > 0)
            ->values();

        $duplicateAnimalIds = $details->pluck('animal_id')->duplicates();
        if ($duplicateAnimalIds->isNotEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Duplicate cow milk rows are not allowed.',
            ], 422);
        }

        $animals = Animal::query()
            ->where('farmer_id', $farmerId)
            ->where('pan_id', $pan->id)
            ->whereIn('id', $details->pluck('animal_id')->all())
            ->get()
            ->keyBy('id');

        if ($animals->count() !== $details->count()) {
            return response()->json([
                'status' => false,
                'message' => 'Every cow milk row must belong to the selected PAN.',
            ], 422);
        }

        $panAnimalCount = Animal::where('farmer_id', $farmerId)
            ->where('pan_id', $pan->id)
            ->count();
        if ($panAnimalCount !== $details->count()) {
            return response()->json([
                'status' => false,
                'message' => 'Please submit milk quantity for every cow in this PAN.',
            ], 422);
        }

        $quantityLiters = round((float) $request->quantity_liters, 2);
        $cowTotal = round((float) $details->sum('final_milk_qty'), 2);
        if (abs($cowTotal - $quantityLiters) > 0.01) {
            return response()->json([
                'status' => false,
                'message' => 'Cow-wise total does not match quantity liters.',
                'data' => [
                    'quantity_liters' => $quantityLiters,
                    'cow_total_liters' => $cowTotal,
                    'difference' => round($cowTotal - $quantityLiters, 2),
                ],
            ], 422);
        }

        $entry = DB::transaction(function () use ($request, $farmerId, $pan, $dateObject, $details, $animals, $quantityLiters, $cowTotal) {
            $panMilkEntry = PanMilkEntry::create([
                'farmer_id' => $farmerId,
                'pan_id' => $pan->id,
                'dairy_id' => $request->dairy_id,
                'date' => $dateObject->format('Y-m-d'),
                'shift' => $request->shift,
                'quantity_liters' => $quantityLiters,
                'cow_total_liters' => $cowTotal,
                'fat' => $request->fat,
                'snf' => $request->snf,
                'rate' => $request->rate,
                'notes' => $request->notes,
            ]);

            foreach ($details as $detail) {
                $quantity = (float) $detail['final_milk_qty'];
                $morning = $request->shift === 'Morning' ? $quantity : 0;
                $afternoon = $request->shift === 'Afternoon' ? $quantity : 0;
                $evening = $request->shift === 'Evening' ? $quantity : 0;

                $milk = MilkProduction::create([
                    'animal_id' => $detail['animal_id'],
                    'dairy_id' => $request->dairy_id,
                    'date' => $dateObject->format('Y-m-d'),
                    'morning_milk' => $morning,
                    'afternoon_milk' => $afternoon,
                    'evening_milk' => $evening,
                    'fat' => $request->fat,
                    'snf' => $request->snf,
                    'rate' => $request->rate,
                ]);

                $animal = $animals->get($detail['animal_id']);
                $panMilkEntry->details()->create([
                    'animal_id' => $detail['animal_id'],
                    'milk_production_id' => $milk->id,
                    'default_milk_per_session' => $animal?->default_milk_per_session,
                    'final_milk_qty' => $quantity,
                ]);
            }

            return $panMilkEntry->load(['pan', 'dairy', 'details.animal']);
        });

        return response()->json([
            'status' => true,
            'message' => 'PAN milk entry added successfully',
            'data' => [
                'id' => $entry->id,
                'pan_id' => $entry->pan_id,
                'pan_name' => $entry->pan->name ?? null,
                'dairy_id' => $entry->dairy_id,
                'dairy_name' => $entry->dairy->dairy_name ?? null,
                'date' => Carbon::parse($entry->date)->format('d/m/Y'),
                'shift' => $entry->shift,
                'quantity_liters' => $entry->quantity_liters,
                'cow_total_liters' => $entry->cow_total_liters,
                'fat' => $entry->fat,
                'snf' => $entry->snf,
                'rate' => $entry->rate,
                'details' => $entry->details->map(fn ($detail) => [
                    'animal_id' => $detail->animal_id,
                    'animal_name' => $detail->animal->animal_name ?? null,
                    'tag_number' => $detail->animal->tag_number ?? null,
                    'default_milk_per_session' => $detail->default_milk_per_session,
                    'final_milk_qty' => $detail->final_milk_qty,
                ])->values(),
            ],
        ], 201);
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

        $dateObject = Carbon::createFromFormat('Y-m-d', $request->date)->startOfDay();
        if ($dateObject->gt(now()->startOfDay())) {
            return response()->json([
                'status' => false,
                'message' => 'Milk date cannot be a future date.',
            ], 422);
        }

        $milk->update([
            'dairy_id' => $request->dairy_id,
            'date' => $dateObject->format('Y-m-d'),
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
