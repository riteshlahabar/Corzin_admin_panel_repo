<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Animal;
use App\Models\Farmer\FeedDietPlan;
use App\Models\Farmer\FeedingRecord;
use App\Models\Farmer\FarmerPan;
use App\Models\Farmer\FeedType;
use App\Models\Farmer\FeedSubtype;
use App\Models\Farmer\MilkProduction;
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
            'rate_per_unit' => 'required|numeric|min:0',
            'feeding_cost' => 'nullable|numeric|min:0',
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

        $submittedSubtypeDetails = (array) $request->input('feed_subtype_details', []);
        $calculatedSubtypeTotal = collect($submittedSubtypeDetails)
            ->sum(fn ($item) => (float) data_get($item, 'quantity', 0));

        $feedingQuantity = $request->filled('feeding_quantity')
            ? (float) $request->input('feeding_quantity')
            : ((float) $request->input('quantity', 0) > 0
                ? (float) $request->input('quantity')
                : (float) $calculatedSubtypeTotal);
        $ratePerUnit = (float) $request->input('rate_per_unit', 0);
        $feedingCost = $request->filled('feeding_cost')
            ? (float) $request->input('feeding_cost')
            : ($feedingQuantity * $ratePerUnit);

        $packageQuantity = $calculatedSubtypeTotal > 0
            ? (float) $calculatedSubtypeTotal
            : (float) $request->input('package_quantity', 0);

        if ($packageQuantity <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'Package quantity must be greater than zero.',
            ], 422);
        }

        if (($feedingQuantity - $packageQuantity) > 0.000001) {
            return response()->json([
                'status' => false,
                'message' => 'Feeding quantity cannot exceed package quantity.',
            ], 422);
        }

        $balanceQuantity = max($packageQuantity - $feedingQuantity, 0);

        $record = FeedingRecord::create([
            'farmer_id' => $request->farmer_id,
            'animal_id' => $request->animal_id,
            'feed_type_id' => $feedType->id,
            'diet_plan_id' => $dietPlan?->id,
            'feed_subtype_details' => $submittedSubtypeDetails,
            'quantity' => $feedingQuantity,
            'package_quantity' => $packageQuantity,
            'feeding_quantity' => $feedingQuantity,
            'balance_quantity' => $balanceQuantity,
            'rate_per_unit' => round($ratePerUnit, 2),
            'feeding_cost' => round($feedingCost, 2),
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
            'data' => $this->transformRecord($record->load(['animal', 'feedType', 'dietPlan'])),
        ], 201);
    }

    public function types(Request $request)
    {
        $farmerId = (int) $request->query('farmer_id', 0);

        $rows = FeedType::query()
            ->where('is_active', true)
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
            ->map(function (FeedType $type) use ($farmerId) {
                $subtypes = $this->visibleSubtypesForType($type->id, $farmerId);

                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'default_unit' => $type->default_unit,
                    'can_add_farmer_subtype' => $farmerId > 0,
                    'subtypes' => $subtypes->map(fn (FeedSubtype $subtype) => [
                        'id' => $subtype->id,
                        'name' => $subtype->name,
                        'farmer_id' => (int) ($subtype->farmer_id ?? 0),
                        'is_farmer_subtype' => (int) ($subtype->farmer_id ?? 0) > 0,
                        'is_editable' => $farmerId > 0 && (int) ($subtype->farmer_id ?? 0) === $farmerId,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();

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

        $farmerId = (int) $request->input('farmer_id');
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

        $createdCount = 0;

        DB::transaction(function () use ($typeId, $farmerId, $subtypes, &$createdCount) {
            $nextSort = ((int) FeedSubtype::query()
                ->where('feed_type_id', $typeId)
                ->where('farmer_id', $farmerId)
                ->max('sort_order')) + 1;

            foreach ($subtypes as $subtypeName) {
                $exists = FeedSubtype::query()
                    ->where('feed_type_id', $typeId)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($subtypeName)])
                    ->where(function ($query) use ($farmerId) {
                        $query->whereNull('farmer_id')
                            ->orWhere('farmer_id', $farmerId);
                    })
                    ->exists();

                if ($exists) {
                    continue;
                }

                FeedSubtype::create([
                    'feed_type_id' => $typeId,
                    'farmer_id' => $farmerId,
                    'name' => $subtypeName,
                    'is_active' => true,
                    'sort_order' => $nextSort++,
                ]);

                $createdCount++;
            }
        });

        if ($createdCount === 0) {
            return response()->json([
                'status' => false,
                'message' => 'All entered subtypes already exist for this farmer.',
            ], 422);
        }

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

        $farmerId = (int) $request->input('farmer_id');
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
            ->where('farmer_id', $farmerId)
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
            ->where(function ($query) use ($farmerId) {
                $query->whereNull('farmer_id')
                    ->orWhere('farmer_id', $farmerId);
            })
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

        $farmerId = (int) $request->input('farmer_id');
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
            ->where('farmer_id', $farmerId)
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

        if ($panId > 0) {
            // PAN selection should show only PAN-wise diet plans.
            $query->where('pan_id', $panId);
        } elseif ($animalId > 0) {
            // Animal selection should show only animal-wise plans.
            $query->where('animal_id', $animalId)
                ->where(function ($nested) {
                    $nested->whereNull('pan_id')->orWhere('pan_id', 0);
                });
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
            $normalizedSubtypes = $this->normalizeSubtypeDetails((array) ($plan->subtype_details ?? []), true);
            $planDryMatter = round(
                collect($normalizedSubtypes)->sum(fn ($item) => (float) data_get($item, 'dry_matter_quantity', 0)),
                2
            );
            $actualDmi = $this->calculateActualDryMatterForPlan($plan);
            $remainingDryMatter = (float) $plan->plan_quantity > 0
                ? round(((float) $plan->remaining_quantity / (float) $plan->plan_quantity) * $planDryMatter, 2)
                : 0.0;

            return [
                'id' => $plan->id,
                'animal_id' => $plan->animal_id,
                'pan_id' => (int) ($plan->pan_id ?? 0),
                'animal_name' => $plan->animal->animal_name ?? '-',
                'tag_number' => $plan->animal->tag_number ?? '-',
                'diet_plan_name' => (string) ($plan->diet_plan_name ?? ''),
                'feed_type_id' => $plan->feed_type_id,
                'feed_type' => $plan->feedType->name ?? '-',
                'reference_date' => $plan->reference_date ? Carbon::parse($plan->reference_date)->toDateString() : null,
                'body_weight' => round((float) ($plan->body_weight ?? 0), 2),
                'milk_production' => round((float) ($plan->milk_production ?? 0), 2),
                'target_dmi' => round((float) ($plan->target_dmi ?? 0), 2),
                'unit' => $plan->unit,
                'days_count' => $daysCount,
                'days_remaining' => $daysRemaining,
                'plan_quantity' => round((float) $plan->plan_quantity, 2),
                'consumed_quantity' => round((float) $plan->consumed_quantity, 2),
                'remaining_quantity' => round((float) $plan->remaining_quantity, 2),
                'actual_dmi' => $actualDmi,
                'plan_dry_matter_quantity' => round((float) ($plan->planned_dry_matter ?? $planDryMatter), 2),
                'remaining_dry_matter_quantity' => $remainingDryMatter,
                'dmi_gap' => round(
                    $plan->dmi_gap !== null
                        ? (float) $plan->dmi_gap
                        : ($planDryMatter - (float) ($plan->target_dmi ?? 0)),
                    2
                ),
                'subtype_details' => $normalizedSubtypes,
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
            'pan_id' => 'nullable|exists:farmer_pans,id',
            'diet_plan_name' => 'required|string|max:255',
            'feed_type_id' => 'required|exists:feed_types,id',
            'reference_date' => 'nullable|date',
            'days_count' => 'nullable|integer|min:1|max:365',
            'unit' => 'required|string|max:30',
            'subtype_details' => 'required|array|min:1',
            'subtype_details.*.name' => 'required|string|max:255',
            'subtype_details.*.quantity' => 'required|numeric|min:0.01',
            'subtype_details.*.dm_percent' => 'required|numeric|min:0.01|max:100',
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
        $dietPlanName = trim((string) $request->input('diet_plan_name'));
        $referenceDate = $request->filled('reference_date')
            ? Carbon::parse((string) $request->input('reference_date'))->toDateString()
            : now()->toDateString();
        $selectedPanId = (int) $request->input('pan_id', 0);

        $total = collect($subtypes)->sum(fn ($item) => (float) $item['quantity']);
        $plannedDryMatter = round(
            collect($subtypes)->sum(fn ($item) => (float) data_get($item, 'dry_matter_quantity', 0)),
            2
        );
        $metrics = $this->resolveDietMetricsValues(
            farmerId: $farmerId,
            animalId: (int) $request->input('animal_id'),
            panId: $selectedPanId,
            forDate: $referenceDate
        );
        $targetDmi = $metrics['target_dmi'];
        $dmiGap = round($plannedDryMatter - $targetDmi, 2);
        $duplicateDietPlan = FeedDietPlan::query()
            ->where('farmer_id', $farmerId)
            ->whereRaw('LOWER(diet_plan_name) = ?', [mb_strtolower($dietPlanName)])
            ->exists();

        if ($duplicateDietPlan) {
            return response()->json([
                'status' => false,
                'message' => 'This diet plan name already exists. Please enter another diet plan name.',
            ], 422);
        }

        $daysCount = $request->filled('days_count')
            ? (int) $request->input('days_count')
            : null;

        $plan = FeedDietPlan::create([
            'farmer_id' => $farmerId,
            'animal_id' => (int) $request->input('animal_id'),
            'pan_id' => $selectedPanId > 0 ? $selectedPanId : null,
            'diet_plan_name' => $dietPlanName,
            'feed_type_id' => (int) $request->input('feed_type_id'),
            'reference_date' => $referenceDate,
            'body_weight' => $metrics['body_weight'],
            'milk_production' => $metrics['milk_production'],
            'target_dmi' => $targetDmi,
            'planned_dry_matter' => $plannedDryMatter,
            'dmi_gap' => $dmiGap,
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
            'pan_id' => 'nullable|exists:farmer_pans,id',
            'reference_date' => 'nullable|date',
            'days_count' => 'nullable|integer|min:1|max:365',
            'feed_type_id' => 'nullable|exists:feed_types,id',
            'unit' => 'nullable|string|max:30',
            'subtype_details' => 'required|array|min:1',
            'subtype_details.*.name' => 'required|string|max:255',
            'subtype_details.*.quantity' => 'required|numeric|min:0.01',
            'subtype_details.*.dm_percent' => 'required|numeric|min:0.01|max:100',
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

        $subtypes = $this->normalizeSubtypeDetails($request->input('subtype_details', []));

        if (empty($subtypes)) {
            return response()->json([
                'status' => false,
                'message' => 'Please add at least one subtype with quantity.',
            ], 422);
        }

        $total = collect($subtypes)->sum(fn ($item) => (float) $item['quantity']);
        $plannedDryMatter = round(
            collect($subtypes)->sum(fn ($item) => (float) data_get($item, 'dry_matter_quantity', 0)),
            2
        );
        $referenceDate = $request->filled('reference_date')
            ? Carbon::parse((string) $request->input('reference_date'))->toDateString()
            : ($plan->reference_date ? Carbon::parse($plan->reference_date)->toDateString() : now()->toDateString());
        $selectedPanId = $request->filled('pan_id')
            ? (int) $request->input('pan_id')
            : (int) ($plan->pan_id ?? 0);
        $metrics = $this->resolveDietMetricsValues(
            farmerId: $farmerId,
            animalId: (int) $plan->animal_id,
            panId: $selectedPanId,
            forDate: $referenceDate
        );
        $targetDmi = $metrics['target_dmi'];

        $updatePayload = [
            'plan_quantity' => round((float) $total, 2),
            'remaining_quantity' => max(round((float) $total - (float) $plan->consumed_quantity, 2), 0),
            'subtype_details' => $subtypes,
            'pan_id' => $selectedPanId > 0 ? $selectedPanId : null,
            'reference_date' => $referenceDate,
            'body_weight' => $metrics['body_weight'],
            'milk_production' => $metrics['milk_production'],
            'target_dmi' => $targetDmi,
            'planned_dry_matter' => $plannedDryMatter,
            'dmi_gap' => round($plannedDryMatter - $targetDmi, 2),
        ];
        if ($request->filled('feed_type_id')) {
            $updatePayload['feed_type_id'] = (int) $request->input('feed_type_id');
        }
        if ($request->filled('unit')) {
            $updatePayload['unit'] = trim((string) $request->input('unit'));
        }
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

    public function dietMetrics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'animal_id' => 'required|exists:animals,id',
            'pan_id' => 'nullable|exists:farmer_pans,id',
            'date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $metrics = $this->resolveDietMetricsValues(
            farmerId: (int) $request->input('farmer_id'),
            animalId: (int) $request->input('animal_id'),
            panId: (int) $request->input('pan_id', 0),
            forDate: $request->filled('date')
                ? Carbon::parse((string) $request->input('date'))->toDateString()
                : now()->toDateString()
        );

        return response()->json([
            'status' => true,
            'message' => 'Diet metrics fetched successfully.',
            'data' => $metrics,
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
        $records = FeedingRecord::with(['animal', 'feedType', 'dietPlan'])
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
            'rate_per_unit' => 'nullable|numeric|min:0',
            'feeding_cost' => 'nullable|numeric|min:0',
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
        $currentDietPlanId = (int) ($record->diet_plan_id ?? 0);
        $nextDietPlanId = $request->filled('diet_plan_id')
            ? (int) $request->input('diet_plan_id')
            : $currentDietPlanId;

        $nextDietPlan = null;
        if ($nextDietPlanId > 0) {
            $nextDietPlan = FeedDietPlan::query()
                ->where('id', $nextDietPlanId)
                ->where('farmer_id', (int) $request->input('farmer_id'))
                ->first();

            if (! $nextDietPlan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Selected diet plan not found.',
                ], 422);
            }
        }

        $currentFeedingQuantity = (float) ($record->feeding_quantity ?? $record->quantity ?? 0);
        $updatedFeedingQuantity = $request->filled('feeding_quantity')
            ? (float) $request->input('feeding_quantity')
            : ($request->filled('quantity')
                ? (float) $request->input('quantity')
                : $currentFeedingQuantity);
        $updatedRatePerUnit = $request->filled('rate_per_unit')
            ? (float) $request->input('rate_per_unit')
            : (float) ($record->rate_per_unit ?? 0);
        $updatedFeedingCost = $request->filled('feeding_cost')
            ? (float) $request->input('feeding_cost')
            : ($updatedFeedingQuantity * $updatedRatePerUnit);
        $updatedSubtypeDetails = $request->input('feed_subtype_details', $record->feed_subtype_details);
        $updatedSubtypeDetails = is_array($updatedSubtypeDetails) ? $updatedSubtypeDetails : [];
        $updatedSubtypeTotal = collect($updatedSubtypeDetails)
            ->sum(fn ($item) => (float) data_get($item, 'quantity', 0));
        $updatedPackageQuantity = $updatedSubtypeTotal > 0
            ? (float) $updatedSubtypeTotal
            : ($request->filled('package_quantity')
                ? (float) $request->input('package_quantity')
                : (float) ($record->package_quantity ?? 0));

        if ($updatedPackageQuantity <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'Package quantity must be greater than zero.',
            ], 422);
        }

        if (($updatedFeedingQuantity - $updatedPackageQuantity) > 0.000001) {
            return response()->json([
                'status' => false,
                'message' => 'Feeding quantity cannot exceed package quantity.',
            ], 422);
        }

        $updatedBalanceQuantity = max($updatedPackageQuantity - $updatedFeedingQuantity, 0);

        DB::transaction(function () use (
            $record,
            $request,
            $currentDietPlanId,
            $nextDietPlanId,
            $nextDietPlan,
            $currentFeedingQuantity,
            $updatedFeedingQuantity,
            $updatedPackageQuantity,
            $updatedBalanceQuantity,
            $updatedSubtypeDetails,
            $updatedRatePerUnit,
            $updatedFeedingCost
        ) {
            if ($currentDietPlanId > 0 && $currentDietPlanId !== $nextDietPlanId) {
                $currentDietPlan = FeedDietPlan::query()->find($currentDietPlanId);
                if ($currentDietPlan) {
                    $this->adjustDietPlanConsumption($currentDietPlan, -$currentFeedingQuantity);
                }
            }

            if ($nextDietPlan && $currentDietPlanId !== $nextDietPlanId) {
                $this->adjustDietPlanConsumption($nextDietPlan, $updatedFeedingQuantity);
            } elseif ($nextDietPlan) {
                $this->adjustDietPlanConsumption(
                    $nextDietPlan,
                    $updatedFeedingQuantity - $currentFeedingQuantity
                );
            }

            $record->update([
                'quantity' => round($updatedFeedingQuantity, 2),
                'unit' => $request->unit,
                'feeding_time' => $request->feeding_time ?: $record->feeding_time,
                'date' => $request->date,
                'notes' => $request->notes,
                'feed_type_id' => $request->filled('feed_type_id')
                    ? $request->feed_type_id
                    : $record->feed_type_id,
                'diet_plan_id' => $nextDietPlan?->id,
                'feed_subtype_details' => $updatedSubtypeDetails,
                'package_quantity' => round($updatedPackageQuantity, 2),
                'feeding_quantity' => round($updatedFeedingQuantity, 2),
                'balance_quantity' => round($updatedBalanceQuantity, 2),
                'rate_per_unit' => round($updatedRatePerUnit, 2),
                'feeding_cost' => round($updatedFeedingCost, 2),
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Feeding entry updated successfully',
            'data' => $this->transformRecord($record->load(['animal', 'feedType', 'dietPlan'])),
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

    private function visibleSubtypesForType(int $feedTypeId, int $farmerId)
    {
        $rows = FeedSubtype::query()
            ->where('feed_type_id', $feedTypeId)
            ->where('is_active', true)
            ->where(function ($query) use ($farmerId) {
                $query->whereNull('farmer_id');
                if ($farmerId > 0) {
                    $query->orWhere('farmer_id', $farmerId);
                }
            })
            ->orderByRaw('CASE WHEN farmer_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $seen = [];

        return $rows->reject(function (FeedSubtype $subtype) use (&$seen) {
            $key = mb_strtolower(trim((string) $subtype->name));
            if ($key === '') {
                return true;
            }
            if (isset($seen[$key])) {
                return true;
            }
            $seen[$key] = true;
            return false;
        })->values();
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

    private function adjustDietPlanConsumption(FeedDietPlan $plan, float $quantityDelta): void
    {
        $nextConsumed = max(round((float) $plan->consumed_quantity + $quantityDelta, 2), 0);
        $nextRemaining = max(round((float) $plan->plan_quantity - $nextConsumed, 2), 0);

        $plan->update([
            'consumed_quantity' => $nextConsumed,
            'remaining_quantity' => $nextRemaining,
        ]);
    }

    private function normalizeSubtypeDetails(array $subtypes, bool $allowMissingDmPercent = false): array
    {
        $bucket = [];
        foreach ($subtypes as $item) {
            $name = trim((string) data_get($item, 'name', ''));
            $qty = (float) data_get($item, 'quantity', 0);
            $dmPercent = (float) data_get($item, 'dm_percent', 0);
            $feedTypeId = (int) data_get($item, 'feed_type_id', 0);
            $feedTypeName = trim((string) data_get($item, 'feed_type_name', data_get($item, 'feed_type', '')));
            if ($allowMissingDmPercent && $dmPercent <= 0) {
                $dmPercent = 100;
            }

            if ($name === '' || $qty <= 0 || $dmPercent <= 0 || $dmPercent > 100) {
                continue;
            }
            $subtypeId = (int) data_get($item, 'subtype_id', 0);
            $typeKey = $feedTypeId > 0
                ? "type:$feedTypeId"
                : ($feedTypeName !== '' ? 'type_name:' . mb_strtolower($feedTypeName) : 'type:any');
            $key = $subtypeId > 0
                ? "$typeKey|id:$subtypeId"
                : "$typeKey|name:" . mb_strtolower($name);
            if (! isset($bucket[$key])) {
                $bucket[$key] = [
                    'subtype_id' => $subtypeId > 0 ? $subtypeId : null,
                    'feed_type_id' => $feedTypeId > 0 ? $feedTypeId : null,
                    'feed_type_name' => $feedTypeName !== '' ? $feedTypeName : null,
                    'name' => $name,
                    'quantity' => 0,
                    'dm_percent' => 0,
                ];
            }

            $existingQty = (float) $bucket[$key]['quantity'];
            $existingDm = (float) $bucket[$key]['dm_percent'];
            $combinedQty = $existingQty + $qty;
            $weightedDm = $combinedQty > 0
                ? ((($existingDm * $existingQty) + ($dmPercent * $qty)) / $combinedQty)
                : $dmPercent;

            $bucket[$key]['quantity'] += $qty;
            $bucket[$key]['dm_percent'] = $weightedDm;
            if (! $bucket[$key]['feed_type_id'] && $feedTypeId > 0) {
                $bucket[$key]['feed_type_id'] = $feedTypeId;
            }
            if (($bucket[$key]['feed_type_name'] ?? null) === null && $feedTypeName !== '') {
                $bucket[$key]['feed_type_name'] = $feedTypeName;
            }
        }

        return collect($bucket)
            ->map(function ($item) {
                $item['quantity'] = round((float) $item['quantity'], 2);
                $item['dm_percent'] = round((float) $item['dm_percent'], 2);
                $item['dry_matter_quantity'] = round(
                    ((float) $item['quantity'] * (float) $item['dm_percent']) / 100,
                    2
                );
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
                $feedTypeId = (int) data_get($item, 'feed_type_id', 0);
                $feedTypeName = mb_strtolower(trim((string) data_get($item, 'feed_type_name', data_get($item, 'feed_type', ''))));
                $typeKey = $feedTypeId > 0 ? "type:$feedTypeId" : ($feedTypeName !== '' ? "type_name:$feedTypeName" : 'type:any');
                $name = mb_strtolower(trim((string) data_get($item, 'name', '')));
                return $id > 0 ? "$typeKey|id:$id" : "$typeKey|name:$name";
            })
            ->unique()
            ->sort()
            ->values()
            ->all();

        return implode('|', $keys);
    }

    private function mergeSubtypeDetails(array $existing, array $incoming): array
    {
        return $this->normalizeSubtypeDetails(array_merge($existing, $incoming), true);
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
            'diet_plan_name' => (string) optional($record->dietPlan)->diet_plan_name,
            'feed_type' => $record->feedType->name ?? '-',
            'quantity' => (float) $record->quantity,
            'package_quantity' => (float) ($record->package_quantity ?? 0),
            'feeding_quantity' => (float) ($record->feeding_quantity ?? $record->quantity),
            'balance_quantity' => (float) ($record->balance_quantity ?? 0),
            'rate_per_unit' => (float) ($record->rate_per_unit ?? 0),
            'feeding_cost' => (float) ($record->feeding_cost ?? 0),
            'feed_subtype_details' => $record->feed_subtype_details ?? [],
            'unit' => $record->unit,
            'feeding_time' => $record->feeding_time,
            'date' => optional($record->date)->format('Y-m-d'),
            'notes' => $record->notes,
        ];
    }

    private function resolveDietMetricsValues(int $farmerId, int $animalId, int $panId, string $forDate): array
    {
        $date = Carbon::parse($forDate)->toDateString();
        $selectedPan = null;
        $animalQuery = Animal::query()->where('farmer_id', $farmerId);
        if ($panId > 0) {
            $selectedPan = FarmerPan::query()
                ->where('id', $panId)
                ->where('farmer_id', $farmerId)
                ->first();
            $animalQuery->where('pan_id', $panId);
        } else {
            $animalQuery->where('id', $animalId);
        }

        $selectedAnimals = $animalQuery->get();
        if ($selectedAnimals->isEmpty()) {
            return [
                'date' => $date,
                'body_weight' => 0.0,
                'milk_production' => 0.0,
                'actual_dmi' => 0.0,
                'target_dmi' => 0.0,
                'dmi_gap' => 0.0,
                'is_non_milking' => false,
            ];
        }

        $animalIds = $selectedAnimals
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        $bodyWeight = (float) Animal::query()
            ->whereIn('id', $animalIds)
            ->sum('weight');
        $typeNames = $selectedAnimals
            ->map(function (Animal $animal) {
                return mb_strtolower(trim((string) optional($animal->animalType)->name));
            })
            ->filter(fn ($type) => $type !== '')
            ->values()
            ->all();
        $isNonMilking = $selectedPan
            ? $this->normalizePanType((string) ($selectedPan->pan_type ?? 'milking')) === 'non_milking'
            : (! empty($typeNames)
                ? collect($typeNames)->every(fn ($type) => $this->isNonMilkingAnimalTypeName((string) $type))
                : false);
        $milkProduction = $this->resolveContextMilkProduction(
            animals: $selectedAnimals->all(),
            pan: $selectedPan,
            date: $date,
            isNonMilking: $isNonMilking,
        );
        $actualDmi = round(
            FeedingRecord::query()
                ->with('dietPlan')
                ->whereDate('date', $date)
                ->whereIn('animal_id', $animalIds)
                ->get()
                ->sum(fn (FeedingRecord $record) => $this->calculateRecordActualDryMatter($record)),
            2
        );
        $targetDmi = $this->computeTargetDmi($bodyWeight, $milkProduction, $isNonMilking);

        return [
            'date' => $date,
            'body_weight' => round($bodyWeight, 2),
            'milk_production' => round($milkProduction, 2),
            'actual_dmi' => $actualDmi,
            'target_dmi' => round($targetDmi, 2),
            'dmi_gap' => round($actualDmi - $targetDmi, 2),
            'is_non_milking' => $isNonMilking,
        ];
    }

    private function computeTargetDmi(float $bodyWeight, float $milkProduction, bool $isNonMilking = false): float
    {
        if ($bodyWeight <= 0) {
            return 0;
        }
        if ($isNonMilking) {
            return $bodyWeight * 0.025;
        }
        if ($milkProduction <= 0) {
            return 0;
        }
        return ($bodyWeight * 0.02) + ($milkProduction * 0.33);
    }

    private function resolveContextMilkProduction(array $animals, ?FarmerPan $pan, string $date, bool $isNonMilking): float
    {
        if ($isNonMilking || empty($animals)) {
            return 0.0;
        }

        $animalIds = collect($animals)
            ->map(fn (Animal $animal) => (int) $animal->id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        if (empty($animalIds)) {
            return 0.0;
        }

        return round((float) MilkProduction::query()
            ->whereDate('date', $date)
            ->whereIn('animal_id', $animalIds)
            ->sum('total_milk'), 2);
    }

    private function isNonMilkingAnimalTypeName(string $typeName): bool
    {
        return str_contains($typeName, 'non')
            || str_contains($typeName, 'dry')
            || str_contains($typeName, 'heifer')
            || str_contains($typeName, 'calf')
            || str_contains($typeName, 'bull');
    }

    private function normalizePanType(string $type): string
    {
        return trim(strtolower($type)) === 'non_milking' ? 'non_milking' : 'milking';
    }

    private function normalizeMilkShifts($milkShifts): array
    {
        $allowed = ['Morning', 'Afternoon', 'Evening'];
        $values = is_array($milkShifts) ? $milkShifts : [];

        return collect($values)
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => in_array($value, $allowed, true))
            ->unique()
            ->values()
            ->all();
    }

    private function calculateActualDryMatterForPlan(FeedDietPlan $plan): float
    {
        return round(
            FeedingRecord::query()
                ->where('diet_plan_id', $plan->id)
                ->get()
                ->sum(fn (FeedingRecord $record) => $this->calculateRecordActualDryMatter($record, $plan)),
            2
        );
    }

    private function calculateRecordActualDryMatter(FeedingRecord $record, ?FeedDietPlan $dietPlan = null): float
    {
        $feedingQuantity = (float) ($record->feeding_quantity ?? $record->quantity ?? 0);
        if ($feedingQuantity <= 0) {
            return 0.0;
        }

        $packageQuantity = (float) ($record->package_quantity ?? 0);
        $recordSubtypes = collect((array) ($record->feed_subtype_details ?? []));
        $packageDryMatter = (float) $recordSubtypes->sum(function ($subtype) {
            $quantity = (float) data_get($subtype, 'quantity', 0);
            $recordDmPercent = (float) data_get($subtype, 'dm_percent', 0);
            return $quantity > 0 && $recordDmPercent > 0
                ? ($quantity * $recordDmPercent) / 100
                : 0;
        });

        if ($packageDryMatter <= 0) {
            return 0.0;
        }

        if ($packageQuantity <= 0) {
            return round($packageDryMatter, 2);
        }

        return round($packageDryMatter * ($feedingQuantity / $packageQuantity), 2);
    }
}
