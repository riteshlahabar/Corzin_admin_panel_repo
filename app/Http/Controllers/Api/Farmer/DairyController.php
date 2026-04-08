<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Dairy\Dairy;
use App\Models\Farmer\MilkProduction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DairyController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'dairy_name' => 'required|string|max:255',
            'gst_no' => 'nullable|string|max:50',
            'contact_number' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'taluka' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $dairy = Dairy::create([
            'farmer_id' => $request->farmer_id,
            'dairy_name' => $request->dairy_name,
            'gst_no' => $request->gst_no,
            'contact_number' => $request->contact_number,
            'address' => $request->address,
            'city' => $request->city,
            'taluka' => $request->taluka,
            'district' => $request->district,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'village' => $request->city,
            'is_active' => true,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Dairy added successfully',
            'data' => $this->transformDairy($dairy->load('farmer')),
        ], 201);
    }

    public function index($farmer_id)
    {
        $dairies = Dairy::with('farmer')
            ->where('farmer_id', $farmer_id)
            ->latest()
            ->get()
            ->map(fn (Dairy $dairy) => $this->transformDairy($dairy));

        return response()->json([
            'status' => true,
            'message' => 'Dairies fetched successfully',
            'data' => $dairies,
        ]);
    }

    public function payments($farmer_id)
    {
        $dairies = Dairy::where('farmer_id', $farmer_id)->latest()->get();
        $dairyIds = $dairies->pluck('id');

        $todayStats = MilkProduction::query()
            ->selectRaw('dairy_id, COALESCE(SUM(total_milk), 0) as today_milk, COALESCE(SUM(total_milk * COALESCE(rate, 0)), 0) as today_payment')
            ->whereIn('dairy_id', $dairyIds)
            ->whereDate('date', now()->toDateString())
            ->groupBy('dairy_id')
            ->get()
            ->keyBy('dairy_id');

        $totalStats = MilkProduction::query()
            ->selectRaw('dairy_id, COALESCE(SUM(total_milk), 0) as total_milk, COALESCE(SUM(total_milk * COALESCE(rate, 0)), 0) as total_payment')
            ->whereIn('dairy_id', $dairyIds)
            ->groupBy('dairy_id')
            ->get()
            ->keyBy('dairy_id');

        $data = $dairies->map(function (Dairy $dairy) use ($todayStats, $totalStats) {
            $today = $todayStats->get($dairy->id);
            $total = $totalStats->get($dairy->id);

            return [
                'id' => $dairy->id,
                'dairy_name' => $dairy->dairy_name,
                'today_payment' => round((float) ($today->today_payment ?? 0), 2),
                'total_payment' => round((float) ($total->total_payment ?? 0), 2),
                'today_milk' => round((float) ($today->today_milk ?? 0), 2),
                'total_milk' => round((float) ($total->total_milk ?? 0), 2),
            ];
        })->values();

        return response()->json([
            'status' => true,
            'message' => 'Dairy payments fetched successfully',
            'data' => $data,
        ]);
    }

    private function transformDairy(Dairy $dairy): array
    {
        return [
            'id' => $dairy->id,
            'farmer_id' => $dairy->farmer_id,
            'farmer_name' => trim(($dairy->farmer->first_name ?? '').' '.($dairy->farmer->last_name ?? '')),
            'dairy_name' => $dairy->dairy_name,
            'gst_no' => $dairy->gst_no,
            'contact_number' => $dairy->contact_number,
            'address' => $dairy->address,
            'city' => $dairy->city,
            'taluka' => $dairy->taluka,
            'district' => $dairy->district,
            'state' => $dairy->state,
            'pincode' => $dairy->pincode,
            'is_active' => $dairy->is_active,
        ];
    }
}
