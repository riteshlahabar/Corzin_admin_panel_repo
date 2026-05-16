<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Animal;
use App\Models\Farmer\FeedDietPlan;
use App\Models\Farmer\FeedingRecord;
use App\Models\Farmer\FeedType;
use App\Models\Farmer\FeedSubtype;
use Carbon\Carbon;
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
            'diet_plan_id' => 'nullable|exists:feed_diet_plans,id',
            'quantity' => 'required|numeric|min:0.01',
            'package_quantity' => 'nullable|numeric|min:0',
            'feeding_quantity' => 'nullable|numeric|min:0',
            'balance_quantity' => 'nullable|numeric|min:0',
            'feed_subtype_details' => 'nullable|array',
            'feed_subtype_details.*.subtype_id' => 'nullable|integer',
            'feed_subtype_details.*.name' => 'required_with:feed_subtype_details|string|max:255',
            'feed_subtype_details.*.quantity' => 'required_with:feed_subtype_details|numeric|min:0',
            'unit' => 'required|string|max:30',
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

        $dietPlan = null;
        if ($request->filled('diet_plan_id')) {
            $dietPlan = FeedDietPlan::query()
                ->where('id', (int) $request->input('diet_plan_id'))
                ->where('farmer_id', (int) $request->input('farmer_id'))
                ->first();
            if (! $dietPlan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Selected diet plan not found.',
                ], 422);
            }
        }

        $calculatedSubtypeTotal = collect($request->input('feed_subtype_details', []))
            ->sum(fn ($item) => (float) data_get($item, 'quantity', 0));

        $feedingQuantity = $request->filled('feeding_quantity')
            ? (float) $request->input('feeding_quantity')
            : ((float) $request->input('quantity', 0) > 0
                ? (float) $request->input('quantity')
                : (float) $calculatedSubtypeTotal);

        $packageQuantity = (float) $calculatedSubtypeTotal;

        $balanceQuantity = $request->filled('balance_quantity')
            ? (float) $request->input('balance_quantity')
            : max($packageQuantity - $feedingQuantity, 0);

        $record = FeedingRecord::create([
            'farmer_id' => $request->farmer_id,
            'animal_id' => $request->animal_id,
            'feed_type_id' => $feedType->id,
            'diet_plan_id' => $dietPlan?->id,
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

        if ($dietPlan) {
            $consumed = (float) $dietPlan->consumed_quantity + $feedingQuantity;
            $remaining = max((float) $dietPlan->plan_quantity - $consumed, 0);
            $dietPlan->update([
                'consumed_quantity' => round($consumed, 2),
                'remaining_quantity' => round($remaining, 2),
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Feeding entry saved successfully',
            'data' => $this->transformRecord($record->load(['animal', 'feedType'])),
        ], 201);
    }

    public function types(Request $request)
    {
        $rows = FeedType::where('is_active', true)
            ->with(['subtypes' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('name')
            ->get();

        $byName = [];
        foreach ($rows as $type) {
            $key = mb_strtolower(trim((string) $type->name));
            if (! isset($byName[$key])) {
                $byName[$key] = $type;
            }
        }

        $types = collect(array_values($byName))
            ->map(fn (FeedType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'default_unit' => $type->default_unit,
                'can_add_farmer_subtype' => $type->subtypes->isEmpty(),
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

    public function createSubtype(Request $request, $feedTypeId = null)
    {
        $routeFeedTypeId = (int) ($feedTypeId ?? $request->route('feedTypeId') ?? 0);
        $bodyFeedTypeId = (int) $request->input('feed_type_id', 0);
        $resolvedFeedTypeId = $bodyFeedTypeId > 0 ? $bodyFeedTypeId : $routeFeedTypeId;
        if ($resolvedFeedTypeId > 0) {
            $request->merge(['feed_type_id' => $resolvedFeedTypeId]);
        }

        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'feed_type_id' => 'required|exists:feed_types,id',
            'subtypes' => 'required|array|min:1',
            'subtypes.*.name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $typeId = (int) $request->input('feed_type_id');
        $baseType = FeedType::query()->find($typeId);
        if (! $baseType) {
            return response()->json([
                'status' => false,
                'message' => 'Feed type not found.',
            ], 404);
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

        $targetType = $baseType;

        DB::transaction(function () use ($targetType, $subtypes) {
            $existingNames = FeedSubtype::query()
                ->where('feed_type_id', $targetType->id)
                ->pluck('name')
                ->map(fn ($name) => mb_strtolower(trim((string) $name)))
                ->all();
            $existingMap = array_flip($existingNames);
            $nextSort = ((int) FeedSubtype::query()->where('feed_type_id', $targetType->id)->max('sort_order')) + 1;

            foreach ($subtypes as $subtypeName) {
                $key = mb_strtolower($subtypeName);
                if (isset($existingMap[$key])) {
                    continue;
                }
                FeedSubtype::create([
                    'feed_type_id' => $targetType->id,
                    'name' => $subtypeName,
                    'is_active' => true,
                    'sort_order' => $nextSort++,
                ]);
                $existingMap[$key] = true;
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'Feed subtype saved successfully.',
        ]);
    }

    public function updateSubtype(Request $request, $feedTypeId, $subtypeId)
    {
        $resolvedFeedTypeId = (int) ($feedTypeId ?? 0);
        $resolvedSubtypeId = (int) ($subtypeId ?? 0);

        $request->merge([
            'feed_type_id' => $request->input('feed_type_id', $resolvedFeedTypeId),
        ]);

        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'feed_type_id' => 'required|exists:feed_types,id',
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $typeId = (int) $request->input('feed_type_id');
        if ($resolvedFeedTypeId > 0 && $typeId !== $resolvedFeedTypeId) {
            return response()->json([
                'status' => false,
                'message' => 'Feed type mismatch.',
            ], 422);
        }

        $subtype = FeedSubtype::query()
            ->where('id', $resolvedSubtypeId)
            ->where('feed_type_id', $typeId)
            ->first();

        if (! $subtype) {
            return response()->json([
                'status' => false,
                'message' => 'Subtype not found.',
            ], 404);
        }

        $name = trim((string) $request->input('name'));
        $exists = FeedSubtype::query()
            ->where('feed_type_id', $typeId)
            ->where('id', '!=', $resolvedSubtypeId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();
        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Subtype already exists.',
            ], 422);
        }

        $subtype->update([
            'name' => $name,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Feed subtype updated successfully.',
        ]);
    }

    public function deleteSubtype(Request $request, $feedTypeId, $subtypeId)
    {
        $resolvedFeedTypeId = (int) ($feedTypeId ?? 0);
        $resolvedSubtypeId = (int) ($subtypeId ?? 0);

        $request->merge([
            'feed_type_id' => $request->input('feed_type_id', $resolvedFeedTypeId),
        ]);

        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'feed_type_id' => 'required|exists:feed_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $typeId = (int) $request->input('feed_type_id');
        if ($resolvedFeedTypeId > 0 && $typeId !== $resolvedFeedTypeId) {
            return response()->json([
                'status' => false,
                'message' => 'Feed type mismatch.',
            ], 422);
        }

        $subtype = FeedSubtype::query()
            ->where('id', $resolvedSubtypeId)
            ->where('feed_type_id', $typeId)
            ->first();

        if (! $subtype) {
            return response()->json([
                'status' => false,
                'message' => 'Subtype not found.',
            ], 404);
        }

        $subtype->delete();

        return response()->json([
            'status' => true,
            'message' => 'Feed subtype deleted successfully.',
        ]);
    }

    public function dietPlans(Request $request, $farmer_id)
    {
        $animalId = (int) $request->query('animal_id', 0);
        $panId = (int) $request->query('pan_id', 0);
        $feedTypeId = (int) $request->query('feed_type_id', 0);

        $query = FeedDietPlan::query()
            ->with(['animal', 'feedType'])
            ->where('farmer_id', (int) $farmer_id)
            ->where('is_active', true)
            ->latest('id');

        if ($animalId > 0) {
            $query->where('animal_id', $animalId);
        } elseif ($panId > 0) {
            $panAnimalIds = Animal::query()
                ->where('farmer_id', (int) $farmer_id)
                ->where('pan_id', $panId)
                ->pluck('id')
                ->all();
            $query->whereIn('animal_id', $panAnimalIds ?: [0]);
        }

        if ($feedTypeId > 0) {
            $query->where('feed_type_id', $feedTypeId);
        }

        $rows = $query->get()->map(function (FeedDietPlan $plan) {
            $daysCount = $plan->days_count !== null ? (int) $plan->days_count : null;
            $daysUsed = $plan->created_at
                ? max(0, (int) Carbon::parse($plan->created_at)->startOfDay()->diffInDays(now()->startOfDay()))
                : 0;
            $daysRemaining = $daysCount !== null
                ? max($daysCount - $daysUsed, 0)
                : null;

            return [
                'id' => $plan->id,
                'animal_id' => $plan->animal_id,
                'animal_name' => $plan->animal->animal_name ?? '-',
                'tag_number' => $plan->animal->tag_number ?? '-',
                'feed_type_id' => $plan->feed_type_id,
                'feed_type' => $plan->feedType->name ?? '-',
                'unit' => $plan->unit,
                'days_count' => $daysCount,
                'days_remaining' => $daysRemaining,
                'plan_quantity' => round((float) $plan->plan_quantity, 2),
                'consumed_quantity' => round((float) $plan->consumed_quantity, 2),
                'remaining_quantity' => round((float) $plan->remaining_quantity, 2),
                'subtype_details' => $plan->subtype_details ?? [],
                'created_at' => optional($plan->created_at)->toDateString(),
            ];
        })->values();

        return response()->json([
            'status' => true,
            'message' => 'Diet plans fetched successfully',
            'data' => $rows,
        ]);
    }

    public function createDietPlan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'animal_id' => 'required|exists:animals,id',
            'feed_type_id' => 'required|exists:feed_types,id',
            'days_count' => 'nullable|integer|min:1|max:365',
            'unit' => 'required|string|max:30',
            'subtype_details' => 'required|array|min:1',
            'subtype_details.*.name' => 'required|string|max:255',
            'subtype_details.*.quantity' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $farmerId = (int) $request->input('farmer_id');
        $animal = Animal::query()
            ->where('id', (int) $request->input('animal_id'))
            ->where('farmer_id', $farmerId)
            ->first();
        if (! $animal) {
            return response()->json([
                'status' => false,
                'message' => 'Selected animal is not valid for this farmer.',
            ], 422);
        }

        $subtypes = $this->normalizeSubtypeDetails($request->input('subtype_details', []));
        if (empty($subtypes)) {
            return response()->json([
                'status' => false,
                'message' => 'Please add at least one subtype with quantity.',
            ], 422);
        }

        $total = collect($subtypes)->sum(fn ($item) => (float) $item['quantity']);
        $incomingSignature = $this->subtypeSignature($subtypes);

        $candidateAnimalIds = [(int) $animal->id];
        $animalPanId = (int) ($animal->pan_id ?? 0);
        if ($animalPanId > 0) {
            $panAnimalIds = Animal::query()
                ->where('farmer_id', $farmerId)
                ->where('pan_id', $animalPanId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            if (! empty($panAnimalIds)) {
                $candidateAnimalIds = array_values(array_unique(array_merge($candidateAnimalIds, $panAnimalIds)));
            }
        }

        $existingPlan = FeedDietPlan::query()
            ->where('farmer_id', $farmerId)
            ->where('feed_type_id', (int) $request->input('feed_type_id'))
            ->where('is_active', true)
            ->whereIn('animal_id', $candidateAnimalIds)
            ->latest('id')
            ->get()
            ->first(function (FeedDietPlan $plan) use ($incomingSignature) {
                $existingSubtypes = $this->normalizeSubtypeDetails((array) ($plan->subtype_details ?? []));
                return $this->subtypeSignature($existingSubtypes) === $incomingSignature;
            });

        if ($existingPlan) {
            $existingSubtypes = $this->normalizeSubtypeDetails((array) ($existingPlan->subtype_details ?? []));
            $mergedSubtypes = $this->mergeSubtypeDetails($existingSubtypes, $subtypes);
            $addedTotal = collect($subtypes)->sum(fn ($item) => (float) ($item['quantity'] ?? 0));

            $existingPlan->update([
                'plan_quantity' => round((float) $existingPlan->plan_quantity + (float) $addedTotal, 2),
                'remaining_quantity' => round((float) $existingPlan->remaining_quantity + (float) $addedTotal, 2),
                'unit' => trim((string) $request->input('unit')) ?: $existingPlan->unit,
                'subtype_details' => $mergedSubtypes,
                'is_active' => true,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Diet plan merged into existing plan successfully.',
                'data' => [
                    'id' => $existingPlan->id,
                    'merged' => true,
                ],
            ], 200);
        }

        $daysCount = $request->filled('days_count')
            ? (int) $request->input('days_count')
            : null;

        $plan = FeedDietPlan::create([
            'farmer_id' => $farmerId,
            'animal_id' => (int) $request->input('animal_id'),
            'feed_type_id' => (int) $request->input('feed_type_id'),
            'days_count' => $daysCount,
            'plan_quantity' => round((float) $total, 2),
            'consumed_quantity' => 0,
            'remaining_quantity' => round((float) $total, 2),
            'unit' => trim((string) $request->input('unit')),
            'subtype_details' => $subtypes,
            'is_active' => true,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Diet plan created successfully.',
            'data' => [
                'id' => $plan->id,
            ],
        ], 201);
    }

    public function updateDietPlan(Request $request, $planId)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'days_count' => 'nullable|integer|min:1|max:365',
            'subtype_details' => 'required|array|min:1',
            'subtype_details.*.name' => 'required|string|max:255',
            'subtype_details.*.quantity' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $plan = FeedDietPlan::query()->find((int) $planId);
        if (! $plan) {
            return response()->json([
                'status' => false,
                'message' => 'Diet plan not found.',
            ], 404);
        }

        $farmerId = (int) $request->input('farmer_id');
        if ((int) $plan->farmer_id !== $farmerId) {
            return response()->json([
                'status' => false,
                'message' => 'You are not allowed to update this diet plan.',
            ], 403);
        }

        $subtypes = collect($request->input('subtype_details', []))
            ->map(fn ($item) => [
                'subtype_id' => data_get($item, 'subtype_id'),
                'name' => trim((string) data_get($item, 'name')),
                'quantity' => (float) data_get($item, 'quantity', 0),
            ])
            ->filter(fn ($item) => $item['name'] !== '' && $item['quantity'] > 0)
            ->values()
            ->all();

        if (empty($subtypes)) {
            return response()->json([
                'status' => false,
                'message' => 'Please add at least one subtype with quantity.',
            ], 422);
        }

        $total = collect($subtypes)->sum(fn ($item) => (float) $item['quantity']);

        $updatePayload = [
            'plan_quantity' => round((float) $total, 2),
            'remaining_quantity' => max(round((float) $total - (float) $plan->consumed_quantity, 2), 0),
            'subtype_details' => $subtypes,
        ];
        if ($request->exists('days_count')) {
            $updatePayload['days_count'] = $request->filled('days_count')
                ? (int) $request->input('days_count')
                : null;
        }

        $plan->update($updatePayload);

        return response()->json([
            'status' => true,
            'message' => 'Diet plan updated successfully.',
        ]);
    }

    public function deleteDietPlan(Request $request, $planId)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $plan = FeedDietPlan::query()->find((int) $planId);
        if (! $plan) {
            return response()->json([
                'status' => false,
                'message' => 'Diet plan not found.',
            ], 404);
        }

        if ((int) $plan->farmer_id !== (int) $request->input('farmer_id')) {
            return response()->json([
                'status' => false,
                'message' => 'You are not allowed to delete this diet plan.',
            ], 403);
        }

        $plan->delete();

        return response()->json([
            'status' => true,
            'message' => 'Diet plan deleted successfully.',
        ]);
    }

    public function createType(Request $request)
    {
        return response()->json([
            'status' => false,
            'message' => 'Feed type can be created only by admin.',
        ], 403);
    }

    public function updateType(Request $request, $feedTypeId)
    {
        return response()->json([
            'status' => false,
            'message' => 'Feed type can be updated only by admin.',
        ], 403);
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
            'unit' => 'required|string|max:30',
            'feeding_time' => 'nullable|in:Morning,Afternoon,Evening',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'feed_type_id' => 'nullable|exists:feed_types,id',
            'diet_plan_id' => 'nullable|exists:feed_diet_plans,id',
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
            'diet_plan_id' => $request->filled('diet_plan_id')
                ? $request->diet_plan_id
                : $record->diet_plan_id,
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
        $today = now()->toDateString();
        $todayFeeding = FeedingRecord::where('farmer_id', $farmer_id)
            ->whereDate('date', $today)
            ->sum('quantity');
        $totalFeeding = FeedingRecord::where('farmer_id', $farmer_id)
            ->sum('quantity');

        return response()->json([
            'status' => true,
            'message' => 'Feeding summary fetched successfully',
            'data' => [
                'today_feeding' => round((float) $todayFeeding, 2),
                'total_feeding' => round((float) $totalFeeding, 2),
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

    private function normalizeSubtypeDetails(array $subtypes): array
    {
        $bucket = [];
        foreach ($subtypes as $item) {
            $name = trim((string) data_get($item, 'name', ''));
            $qty = (float) data_get($item, 'quantity', 0);
            if ($name === '' || $qty <= 0) {
                continue;
            }
            $subtypeId = (int) data_get($item, 'subtype_id', 0);
            $key = $subtypeId > 0 ? "id:$subtypeId" : 'name:' . mb_strtolower($name);
            if (! isset($bucket[$key])) {
                $bucket[$key] = [
                    'subtype_id' => $subtypeId > 0 ? $subtypeId : null,
                    'name' => $name,
                    'quantity' => 0,
                ];
            }
            $bucket[$key]['quantity'] += $qty;
        }

        return collect($bucket)
            ->map(function ($item) {
                $item['quantity'] = round((float) $item['quantity'], 2);
                return $item;
            })
            ->values()
            ->all();
    }

    private function subtypeSignature(array $subtypes): string
    {
        $keys = collect($subtypes)
            ->map(function ($item) {
                $id = (int) data_get($item, 'subtype_id', 0);
                $name = mb_strtolower(trim((string) data_get($item, 'name', '')));
                return $id > 0 ? "id:$id" : "name:$name";
            })
            ->unique()
            ->sort()
            ->values()
            ->all();

        return implode('|', $keys);
    }

    private function mergeSubtypeDetails(array $existing, array $incoming): array
    {
        return $this->normalizeSubtypeDetails(array_merge($existing, $incoming));
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
            'diet_plan_id' => $record->diet_plan_id,
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
