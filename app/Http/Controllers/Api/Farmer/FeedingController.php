<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\FeedingRecord;
use App\Models\Farmer\FeedType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeedingController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'animal_id' => 'required|exists:animals,id',
            'feed_type' => 'nullable|string|max:255',
            'feed_type_id' => 'nullable|exists:feed_types,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit' => 'required|in:Kg,Gram',
            'feeding_time' => 'nullable|in:Morning,Afternoon,Evening',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $feedType = $this->resolveFeedType($request);
        if (! $feedType) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid feed type supplied.',
            ], 422);
        }

        $record = FeedingRecord::create([
            'farmer_id' => $request->farmer_id,
            'animal_id' => $request->animal_id,
            'feed_type_id' => $feedType->id,
            'quantity' => $request->quantity,
            'unit' => $request->unit,
            'feeding_time' => $request->feeding_time ?: 'Morning',
            'date' => $request->date,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Feeding entry saved successfully',
            'data' => $this->transformRecord($record->load(['animal', 'feedType'])),
        ], 201);
    }

    public function types()
    {
        $types = FeedType::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (FeedType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'default_unit' => $type->default_unit,
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Feed types fetched successfully',
            'data' => $types,
        ]);
    }

    public function list($farmer_id)
    {
        $records = FeedingRecord::with(['animal', 'feedType'])
            ->where('farmer_id', $farmer_id)
            ->latest('date')
            ->latest('id')
            ->get()
            ->map(fn (FeedingRecord $record) => $this->transformRecord($record));

        return response()->json([
            'status' => true,
            'message' => 'Feeding records fetched successfully',
            'data' => $records,
        ]);
    }

    public function summary($farmer_id)
    {
        $records = FeedingRecord::where('farmer_id', $farmer_id)->get();

        return response()->json([
            'status' => true,
            'message' => 'Feeding summary fetched successfully',
            'data' => [
                'today_feeding' => round($records->where('date', now()->toDateString())->sum('quantity'), 2),
                'total_feeding' => round($records->sum('quantity'), 2),
                'unit' => 'Kg',
            ],
        ]);
    }

    private function resolveFeedType(Request $request): ?FeedType
    {
        if ($request->filled('feed_type_id')) {
            return FeedType::find($request->feed_type_id);
        }

        if ($request->filled('feed_type')) {
            return FeedType::where('name', $request->feed_type)->first();
        }

        return null;
    }

    private function transformRecord(FeedingRecord $record): array
    {
        return [
            'id' => $record->id,
            'farmer_id' => $record->farmer_id,
            'animal_id' => $record->animal_id,
            'animal_name' => $record->animal->animal_name ?? '-',
            'tag_number' => $record->animal->tag_number ?? '-',
            'feed_type_id' => $record->feed_type_id,
            'feed_type' => $record->feedType->name ?? '-',
            'quantity' => (float) $record->quantity,
            'unit' => $record->unit,
            'feeding_time' => $record->feeding_time,
            'date' => optional($record->date)->format('Y-m-d'),
            'notes' => $record->notes,
        ];
    }
}
