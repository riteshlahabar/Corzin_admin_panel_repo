<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\FeedingRecord;
use App\Models\Farmer\FeedType;
use App\Models\Farmer\FeedSubtype;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'package_quantity' => 'nullable|numeric|min:0',
            'feeding_quantity' => 'nullable|numeric|min:0',
            'balance_quantity' => 'nullable|numeric|min:0',
            'feed_subtype_details' => 'nullable|array',
            'feed_subtype_details.*.subtype_id' => 'nullable|integer',
            'feed_subtype_details.*.name' => 'required_with:feed_subtype_details|string|max:255',
            'feed_subtype_details.*.quantity' => 'required_with:feed_subtype_details|numeric|min:0',
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

        $calculatedSubtypeTotal = collect($request->input('feed_subtype_details', []))
            ->sum(fn ($item) => (float) data_get($item, 'quantity', 0));

        $feedingQuantity = $request->filled('feeding_quantity')
            ? (float) $request->input('feeding_quantity')
            : ((float) $request->input('quantity', 0) > 0
                ? (float) $request->input('quantity')
                : (float) $calculatedSubtypeTotal);

        $packageQuantity = $request->filled('package_quantity')
            ? (float) $request->input('package_quantity')
            : (float) ($feedType->package_quantity ?? 0);

        $balanceQuantity = $request->filled('balance_quantity')
            ? (float) $request->input('balance_quantity')
            : max($packageQuantity - $feedingQuantity, 0);

        $record = FeedingRecord::create([
            'farmer_id' => $request->farmer_id,
            'animal_id' => $request->animal_id,
            'feed_type_id' => $feedType->id,
            'feed_subtype_details' => $request->input('feed_subtype_details'),
            'quantity' => $feedingQuantity,
            'package_quantity' => $packageQuantity,
            'feeding_quantity' => $feedingQuantity,
            'balance_quantity' => $balanceQuantity,
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

    public function types(Request $request)
    {
        $farmerId = (int) $request->query('farmer_id', 0);

        $types = FeedType::where('is_active', true)
            ->where(function ($query) use ($farmerId) {
                $query->whereNull('farmer_id');
                if ($farmerId > 0) {
                    $query->orWhere('farmer_id', $farmerId);
                }
            })
            ->with(['subtypes' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->map(fn (FeedType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'default_unit' => $type->default_unit,
                'package_quantity' => (float) ($type->package_quantity ?? 0),
                'farmer_id' => $type->farmer_id,
                'subtypes' => $type->subtypes->map(fn (FeedSubtype $subtype) => [
                    'id' => $subtype->id,
                    'name' => $subtype->name,
                ])->values(),
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Feed types fetched successfully',
            'data' => $types,
        ]);
    }

    public function createType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'name' => 'required|string|max:255',
            'default_unit' => 'nullable|in:Kg,Gram',
            'package_quantity' => 'required|numeric|min:0.01',
            'subtypes' => 'required|array|min:1',
            'subtypes.*.name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $name = trim((string) $request->input('name'));
        $farmerId = (int) $request->input('farmer_id');

        $exists = FeedType::where('farmer_id', $farmerId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();
        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Feed type already exists for this farmer.',
            ], 422);
        }

        $subtypes = collect($request->input('subtypes', []))
            ->map(fn ($item) => trim((string) data_get($item, 'name', '')))
            ->filter()
            ->unique(fn ($value) => mb_strtolower($value))
            ->values();

        if ($subtypes->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'At least one valid subtype is required.',
            ], 422);
        }

        $type = DB::transaction(function () use ($request, $name, $farmerId, $subtypes) {
            $type = FeedType::create([
                'farmer_id' => $farmerId,
                'name' => $name,
                'default_unit' => $request->input('default_unit', 'Kg'),
                'package_quantity' => $request->input('package_quantity'),
                'is_active' => true,
            ]);

            $rows = [];
            foreach ($subtypes as $index => $subtypeName) {
                $rows[] = [
                    'feed_type_id' => $type->id,
                    'name' => $subtypeName,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            FeedSubtype::insert($rows);

            return $type->fresh(['subtypes']);
        });

        return response()->json([
            'status' => true,
            'message' => 'Feed type created successfully',
            'data' => [
                'id' => $type->id,
                'name' => $type->name,
                'default_unit' => $type->default_unit,
                'package_quantity' => (float) $type->package_quantity,
                'subtypes' => $type->subtypes->map(fn (FeedSubtype $subtype) => [
                    'id' => $subtype->id,
                    'name' => $subtype->name,
                ])->values(),
            ],
        ], 201);
    }

    public function updateType(Request $request, $feedTypeId)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'name' => 'required|string|max:255',
            'default_unit' => 'nullable|in:Kg,Gram',
            'package_quantity' => 'required|numeric|min:0.01',
            'subtypes' => 'required|array|min:1',
            'subtypes.*.name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $type = FeedType::find($feedTypeId);
        if (! $type) {
            return response()->json([
                'status' => false,
                'message' => 'Feed type not found.',
            ], 404);
        }

        $farmerId = (int) $request->input('farmer_id');
        if ((int) ($type->farmer_id ?? 0) !== $farmerId) {
            return response()->json([
                'status' => false,
                'message' => 'You are not allowed to update this feed type.',
            ], 403);
        }

        $name = trim((string) $request->input('name'));
        $exists = FeedType::where('farmer_id', $farmerId)
            ->where('id', '!=', $type->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();
        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Feed type already exists for this farmer.',
            ], 422);
        }

        $subtypes = collect($request->input('subtypes', []))
            ->map(fn ($item) => trim((string) data_get($item, 'name', '')))
            ->filter()
            ->unique(fn ($value) => mb_strtolower($value))
            ->values();

        if ($subtypes->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'At least one valid subtype is required.',
            ], 422);
        }

        $type = DB::transaction(function () use ($request, $type, $name, $subtypes) {
            $type->update([
                'name' => $name,
                'default_unit' => $request->input('default_unit', 'Kg'),
                'package_quantity' => $request->input('package_quantity'),
            ]);

            FeedSubtype::where('feed_type_id', $type->id)->delete();
            $rows = [];
            foreach ($subtypes as $index => $subtypeName) {
                $rows[] = [
                    'feed_type_id' => $type->id,
                    'name' => $subtypeName,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            FeedSubtype::insert($rows);

            return $type->fresh(['subtypes']);
        });

        return response()->json([
            'status' => true,
            'message' => 'Feed type updated successfully',
            'data' => [
                'id' => $type->id,
                'name' => $type->name,
                'default_unit' => $type->default_unit,
                'package_quantity' => (float) $type->package_quantity,
                'subtypes' => $type->subtypes->map(fn (FeedSubtype $subtype) => [
                    'id' => $subtype->id,
                    'name' => $subtype->name,
                ])->values(),
            ],
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

    public function update(Request $request, $feeding_id)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'quantity' => 'required|numeric|min:0.01',
            'package_quantity' => 'nullable|numeric|min:0',
            'feeding_quantity' => 'nullable|numeric|min:0',
            'balance_quantity' => 'nullable|numeric|min:0',
            'feed_subtype_details' => 'nullable|array',
            'feed_subtype_details.*.subtype_id' => 'nullable|integer',
            'feed_subtype_details.*.name' => 'required_with:feed_subtype_details|string|max:255',
            'feed_subtype_details.*.quantity' => 'required_with:feed_subtype_details|numeric|min:0',
            'unit' => 'required|in:Kg,Gram',
            'feeding_time' => 'nullable|in:Morning,Afternoon,Evening',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'feed_type_id' => 'nullable|exists:feed_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $record = FeedingRecord::find($feeding_id);
        if (! $record) {
            return response()->json([
                'status' => false,
                'message' => 'Feeding record not found.',
            ], 404);
        }

        if ((int) $record->farmer_id !== (int) $request->farmer_id) {
            return response()->json([
                'status' => false,
                'message' => 'You are not allowed to update this record.',
            ], 403);
        }

        $record->update([
            'quantity' => $request->quantity,
            'unit' => $request->unit,
            'feeding_time' => $request->feeding_time ?: $record->feeding_time,
            'date' => $request->date,
            'notes' => $request->notes,
            'feed_type_id' => $request->filled('feed_type_id')
                ? $request->feed_type_id
                : $record->feed_type_id,
            'feed_subtype_details' => $request->input('feed_subtype_details', $record->feed_subtype_details),
            'package_quantity' => $request->filled('package_quantity')
                ? $request->package_quantity
                : $record->package_quantity,
            'feeding_quantity' => $request->filled('feeding_quantity')
                ? $request->feeding_quantity
                : $record->feeding_quantity,
            'balance_quantity' => $request->filled('balance_quantity')
                ? $request->balance_quantity
                : $record->balance_quantity,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Feeding entry updated successfully',
            'data' => $this->transformRecord($record->load(['animal', 'feedType'])),
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
            'package_quantity' => (float) ($record->package_quantity ?? 0),
            'feeding_quantity' => (float) ($record->feeding_quantity ?? $record->quantity),
            'balance_quantity' => (float) ($record->balance_quantity ?? 0),
            'feed_subtype_details' => $record->feed_subtype_details ?? [],
            'unit' => $record->unit,
            'feeding_time' => $record->feeding_time,
            'date' => optional($record->date)->format('Y-m-d'),
            'notes' => $record->notes,
        ];
    }
}
