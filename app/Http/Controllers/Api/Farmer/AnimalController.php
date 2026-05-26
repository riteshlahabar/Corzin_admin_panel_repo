<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Farmer\Animal;
use App\Models\Farmer\AnimalType;
use App\Models\Farmer\AnimalLifecycleHistory;
use App\Models\Farmer\FarmerPan;
use App\Models\Farmer\Farmer;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnimalController extends Controller
{
    public function __construct(protected FirebaseService $firebaseService)
    {
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'animal_name' => 'required|string|max:255',
            'tag_number' => 'required|string|max:255',
            'animal_type_id' => 'required|exists:animal_types,id',
            'mother_animal_id' => 'nullable|exists:animals,id',
            'lactation_number' => 'nullable|integer|min:0',
            'ai_date' => 'nullable|date_format:d/m/Y',
            'breed_name' => 'nullable|string|max:255',
            'birth_date' => 'required|date_format:d/m/Y',
            'purchase_date' => 'nullable|date_format:d/m/Y',
            'age' => 'nullable|integer|min:0',
            'gender' => 'required|string',
            'weight' => 'required|numeric|min:0.01',
            'default_milk_per_session' => 'required|numeric|min:0',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp,jfif|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        if ($request->filled('mother_animal_id')) {
            $motherAnimal = Animal::where('id', $request->mother_animal_id)
                ->where('farmer_id', $request->farmer_id)
                ->first();

            if (! $motherAnimal) {
                return response()->json([
                    'status' => false,
                    'message' => ['mother_animal_id' => ['Selected mother animal is invalid.']],
                ], 422);
            }
        }

        $birthDateObj = Carbon::createFromFormat('d/m/Y', $request->birth_date);
        $birthDate = $birthDateObj->format('Y-m-d');
        $purchaseDate = $request->filled('purchase_date')
            ? Carbon::createFromFormat('d/m/Y', $request->purchase_date)->format('Y-m-d')
            : null;
        $aiDate = $request->filled('ai_date')
            ? Carbon::createFromFormat('d/m/Y', $request->ai_date)->format('Y-m-d')
            : null;
        $age = max($birthDateObj->age, 0);
        $imagePath = null;

        if ($request->hasFile('image')) {
            $directory = public_path('assets/animal_images');
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($directory, $filename);
            $imagePath = 'assets/animal_images/' . $filename;
        }

        $animalTypeName = AnimalType::find($request->animal_type_id)->name ?? 'XX';
        $animalType = strtoupper(substr($animalTypeName, 0, 2));
        $prefix = "C/$animalType";
        $lastAnimal = Animal::where('unique_id', 'LIKE', "$prefix/%")->orderBy('id', 'desc')->first();
        $nextNumber = $lastAnimal ? str_pad(((int) substr($lastAnimal->unique_id, -3)) + 1, 3, '0', STR_PAD_LEFT) : '001';
        $uniqueId = "$prefix/$nextNumber";

        $animal = Animal::create([
            'farmer_id' => $request->farmer_id,
            'unique_id' => $uniqueId,
            'animal_name' => $request->animal_name,
            'tag_number' => $request->tag_number,
            'animal_type_id' => $request->animal_type_id,
            'mother_animal_id' => $request->filled('mother_animal_id') ? (int) $request->mother_animal_id : null,
            'lactation_number' => $request->filled('lactation_number') ? (int) $request->lactation_number : null,
            'ai_date' => $aiDate,
            'breed_name' => $request->filled('breed_name') ? trim((string) $request->breed_name) : null,
            'age' => $age,
            'birth_date' => $birthDate,
            'purchase_date' => $purchaseDate,
            'gender' => $request->gender,
            'weight' => $request->weight,
            'default_milk_per_session' => $request->default_milk_per_session,
            'image' => $imagePath,
            'lifecycle_status' => 'active',
            'is_active' => true,
            'is_for_sale' => false,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Animal created successfully',
            'data' => $this->transformAnimal($animal->load(['animalType', 'motherAnimal'])),
        ]);
    }

    public function listByFarmer(Request $request, $farmer_id)
    {
        try {
            $query = Animal::with(['animalType', 'motherAnimal', 'pan'])->where('farmer_id', $farmer_id);

            if (! $request->boolean('include_inactive')) {
                $query->where('is_active', true);
            }

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('lifecycle_status', $request->status);
            }

            $animals = $query->latest()->get()->map(fn ($animal) => $this->transformAnimal($animal));

            return response()->json([
                'status' => true,
                'message' => 'Animals fetched successfully',
                'data' => $animals,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch animals',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function panList($farmerId)
    {
        if (! Farmer::query()->whereKey($farmerId)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Farmer not found.',
            ], 404);
        }

        $pans = FarmerPan::query()
            ->with([
                'animals' => function ($query) {
                    $query->with(['animalType', 'motherAnimal', 'pan'])->latest();
                },
            ])
            ->where('farmer_id', (int) $farmerId)
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Farmer PAN list fetched successfully.',
            'data' => $pans->map(function (FarmerPan $pan) {
                $panType = $this->normalizePanType($pan->pan_type);
                return [
                    'id' => $pan->id,
                    'name' => $pan->name,
                    'pan_type' => $panType,
                    'milk_shifts' => $this->normalizeMilkShifts(
                        $pan->milk_shifts,
                        allowEmpty: $panType === 'non_milking'
                    ),
                    'animals_count' => $pan->animals->count(),
                    'animals' => $pan->animals->map(fn ($animal) => $this->transformAnimal($animal))->values(),
                    'created_at' => optional($pan->created_at)->toDateTimeString(),
                ];
            })->values(),
        ]);
    }

    public function createPan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'name' => 'required|string|max:255',
            'animal_ids' => 'required|array|min:1',
            'animal_ids.*' => 'integer|exists:animals,id',
            'pan_type' => 'nullable|string|in:milking,non_milking',
            'milk_shifts' => 'nullable|array',
            'milk_shifts.*' => 'string|in:Morning,Afternoon,Evening',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $farmerId = (int) $request->farmer_id;
        $animalIds = collect($request->input('animal_ids', []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $eligibleAnimalIds = Animal::query()
            ->where('farmer_id', $farmerId)
            ->whereIn('id', $animalIds)
            ->where(function ($query) {
                $query->whereNull('pan_id')->orWhere('pan_id', 0);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
        $blockedAnimalIds = $animalIds->diff($eligibleAnimalIds)->values();
        if ($blockedAnimalIds->isNotEmpty()) {
            return response()->json([
                'status' => false,
                'message' => [
                    'animal_ids' => [
                        'Some selected animals already belong to another PAN. Please use PAN transfer.',
                    ],
                ],
            ], 422);
        }
        $panType = $this->normalizePanType($request->input('pan_type', 'milking'));
        $milkShifts = $this->normalizeMilkShifts(
            $request->input('milk_shifts', []),
            allowEmpty: $panType === 'non_milking'
        );
        if ($panType === 'milking' && empty($milkShifts)) {
            return response()->json([
                'status' => false,
                'message' => ['milk_shifts' => ['Milk shifts are required for Milking PAN.']],
            ], 422);
        }

        try {
            $pan = DB::transaction(function () use ($request, $farmerId, $eligibleAnimalIds, $panType, $milkShifts) {
                $pan = FarmerPan::create([
                    'farmer_id' => $farmerId,
                    'name' => trim((string) $request->name),
                    'pan_type' => $panType,
                    'milk_shifts' => $milkShifts,
                ]);

                $animals = Animal::query()
                    ->where('farmer_id', $farmerId)
                    ->whereIn('id', $eligibleAnimalIds)
                    ->get();

                foreach ($animals as $animal) {
                    $fromPanId = $animal->pan_id;
                    if ($fromPanId !== $pan->id) {
                        $this->logPanTransferForAnimal(
                            $animal,
                            $fromPanId,
                            $pan->id,
                            'Assigned in PAN creation.'
                        );
                    }

                    $animal->update([
                        'pan_id' => $pan->id,
                    ]);
                }

                return $pan;
            });

            $pan->load(['animals' => fn ($query) => $query->with(['animalType', 'motherAnimal', 'pan'])->latest()]);

            return response()->json([
                'status' => true,
                'message' => 'PAN created successfully.',
                'data' => [
                    'id' => $pan->id,
                    'name' => $pan->name,
                    'pan_type' => $this->normalizePanType($pan->pan_type),
                    'milk_shifts' => $this->normalizeMilkShifts(
                        $pan->milk_shifts,
                        allowEmpty: $this->normalizePanType($pan->pan_type) === 'non_milking'
                    ),
                    'animals_count' => $pan->animals->count(),
                    'animals' => $pan->animals->map(fn ($animal) => $this->transformAnimal($animal))->values(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create PAN.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updatePan(Request $request, $panId)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'name' => 'required|string|max:255',
            'animal_ids' => 'nullable|array',
            'animal_ids.*' => 'integer|exists:animals,id',
            'pan_type' => 'nullable|string|in:milking,non_milking',
            'milk_shifts' => 'nullable|array',
            'milk_shifts.*' => 'string|in:Morning,Afternoon,Evening',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $farmerId = (int) $request->farmer_id;
        $pan = FarmerPan::query()
            ->where('id', (int) $panId)
            ->where('farmer_id', $farmerId)
            ->first();

        if (! $pan) {
            return response()->json([
                'status' => false,
                'message' => 'PAN not found.',
            ], 404);
        }

        $targetIds = collect($request->input('animal_ids', []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $eligibleTargetIds = Animal::query()
            ->where('farmer_id', $farmerId)
            ->whereIn('id', $targetIds)
            ->where(function ($query) use ($pan) {
                $query->whereNull('pan_id')
                    ->orWhere('pan_id', 0)
                    ->orWhere('pan_id', $pan->id);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
        $blockedTargetIds = $targetIds->diff($eligibleTargetIds)->values();
        if ($blockedTargetIds->isNotEmpty()) {
            return response()->json([
                'status' => false,
                'message' => [
                    'animal_ids' => [
                        'Some selected animals already belong to another PAN. Please use PAN transfer.',
                    ],
                ],
            ], 422);
        }
        $panType = $this->normalizePanType($request->input('pan_type', $pan->pan_type ?? 'milking'));
        $milkShifts = $this->normalizeMilkShifts(
            $request->input('milk_shifts', []),
            allowEmpty: $panType === 'non_milking'
        );
        if ($panType === 'milking' && empty($milkShifts)) {
            return response()->json([
                'status' => false,
                'message' => ['milk_shifts' => ['Milk shifts are required for Milking PAN.']],
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $pan, $farmerId, $eligibleTargetIds, $panType, $milkShifts) {
                $pan->update([
                    'name' => trim((string) $request->name),
                    'pan_type' => $panType,
                    'milk_shifts' => $milkShifts,
                ]);

                if (! $request->has('animal_ids')) {
                    return;
                }

                Animal::query()
                    ->where('farmer_id', $farmerId)
                    ->where('pan_id', $pan->id)
                    ->whereNotIn('id', $eligibleTargetIds)
                    ->update(['pan_id' => null]);

                $animals = Animal::query()
                    ->where('farmer_id', $farmerId)
                    ->whereIn('id', $eligibleTargetIds)
                    ->get();

                foreach ($animals as $animal) {
                    $fromPanId = $animal->pan_id;
                    if ($fromPanId !== $pan->id) {
                        $this->logPanTransferForAnimal(
                            $animal,
                            $fromPanId,
                            $pan->id,
                            'Moved while updating PAN members.'
                        );
                    }

                    $animal->update([
                        'pan_id' => $pan->id,
                    ]);
                }
            });

            $pan->refresh()->load(['animals' => fn ($query) => $query->with(['animalType', 'motherAnimal', 'pan'])->latest()]);

            return response()->json([
                'status' => true,
                'message' => 'PAN updated successfully.',
                'data' => [
                    'id' => $pan->id,
                    'name' => $pan->name,
                    'pan_type' => $this->normalizePanType($pan->pan_type),
                    'milk_shifts' => $this->normalizeMilkShifts(
                        $pan->milk_shifts,
                        allowEmpty: $this->normalizePanType($pan->pan_type) === 'non_milking'
                    ),
                    'animals_count' => $pan->animals->count(),
                    'animals' => $pan->animals->map(fn ($animal) => $this->transformAnimal($animal))->values(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update PAN.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function transferPanAnimal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'animal_id' => 'required|integer|exists:animals,id',
            'to_pan_id' => 'required|integer|exists:farmer_pans,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $farmerId = (int) $request->farmer_id;
        $animal = Animal::query()
            ->with(['animalType', 'motherAnimal', 'pan'])
            ->where('id', (int) $request->animal_id)
            ->where('farmer_id', $farmerId)
            ->first();

        if (! $animal) {
            return response()->json([
                'status' => false,
                'message' => 'Animal not found.',
            ], 404);
        }

        $toPan = FarmerPan::query()
            ->where('id', (int) $request->to_pan_id)
            ->where('farmer_id', $farmerId)
            ->first();

        if (! $toPan) {
            return response()->json([
                'status' => false,
                'message' => 'Destination PAN not found.',
            ], 404);
        }

        if (
            $this->normalizePanType($toPan->pan_type) === 'milking' &&
            ! $this->isMilkingAnimalType(optional($animal->animalType)->name)
        ) {
            return response()->json([
                'status' => false,
                'message' => [
                    'animal_id' => ['Only milking cows can be transferred to a Milking PAN.'],
                ],
            ], 422);
        }

        $fromPanId = $animal->pan_id;
        if ($fromPanId === $toPan->id) {
            return response()->json([
                'status' => true,
                'message' => 'Animal is already in selected PAN.',
                'data' => $this->transformAnimal($animal),
            ]);
        }

        try {
            DB::transaction(function () use ($animal, $fromPanId, $toPan, $request) {
                $animal->update([
                    'pan_id' => $toPan->id,
                ]);

                $this->logPanTransferForAnimal(
                    $animal->fresh(),
                    $fromPanId,
                    $toPan->id,
                    $request->notes
                );
            });

            return response()->json([
                'status' => true,
                'message' => 'Animal PAN transferred successfully.',
                'data' => $this->transformAnimal($animal->fresh()->load(['animalType', 'motherAnimal', 'pan'])),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to transfer PAN.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $animal_id)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'animal_name' => 'required|string|max:255',
            'tag_number' => 'required|string|max:255',
            'animal_type_id' => 'required|exists:animal_types,id',
            'mother_animal_id' => 'nullable|exists:animals,id',
            'lactation_number' => 'nullable|integer|min:0',
            'ai_date' => 'nullable|date_format:d/m/Y',
            'breed_name' => 'nullable|string|max:255',
            'birth_date' => 'required|date_format:d/m/Y',
            'purchase_date' => 'nullable|date_format:d/m/Y',
            'gender' => 'required|string',
            'weight' => 'nullable|numeric',
            'default_milk_per_session' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,jfif|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->filled('mother_animal_id')) {
            $motherAnimal = Animal::where('id', $request->mother_animal_id)
                ->where('farmer_id', $request->farmer_id)
                ->first();

            if (! $motherAnimal) {
                return response()->json([
                    'status' => false,
                    'message' => ['mother_animal_id' => ['Selected mother animal is invalid.']],
                ], 422);
            }
        }

        $animal = Animal::where('id', $animal_id)
            ->where('farmer_id', $request->farmer_id)
            ->first();

        if (! $animal) {
            return response()->json([
                'status' => false,
                'message' => 'Animal not found.',
            ], 404);
        }

        $birthDateObj = Carbon::createFromFormat('d/m/Y', $request->birth_date);
        $birthDate = $birthDateObj->format('Y-m-d');
        $purchaseDate = $request->filled('purchase_date')
            ? Carbon::createFromFormat('d/m/Y', $request->purchase_date)->format('Y-m-d')
            : ($request->has('purchase_date') ? null : $animal->purchase_date);
        $aiDate = $request->filled('ai_date')
            ? Carbon::createFromFormat('d/m/Y', $request->ai_date)->format('Y-m-d')
            : null;
        $age = max($birthDateObj->age, 0);
        $imagePath = $animal->image;

        if ($request->hasFile('image')) {
            $directory = public_path('assets/animal_images');
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($directory, $filename);
            $imagePath = 'assets/animal_images/' . $filename;
        }

        $animal->update([
            'animal_name' => $request->animal_name,
            'tag_number' => $request->tag_number,
            'animal_type_id' => $request->animal_type_id,
            'mother_animal_id' => $request->filled('mother_animal_id') ? (int) $request->mother_animal_id : null,
            'lactation_number' => $request->filled('lactation_number') ? (int) $request->lactation_number : null,
            'ai_date' => $aiDate,
            'breed_name' => $request->filled('breed_name') ? trim((string) $request->breed_name) : null,
            'age' => $age,
            'birth_date' => $birthDate,
            'purchase_date' => $purchaseDate,
            'gender' => $request->gender,
            'weight' => $request->weight,
            'default_milk_per_session' => $request->default_milk_per_session,
            'image' => $imagePath,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Animal updated successfully',
            'data' => $this->transformAnimal($animal->fresh()->load(['animalType', 'motherAnimal'])),
        ], 200);
    }

    public function markForSale(Request $request, $animalId)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'selling_price' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $animal = Animal::with('farmer')
            ->where('id', $animalId)
            ->where('farmer_id', $request->farmer_id)
            ->first();

        if (! $animal) {
            return response()->json([
                'status' => false,
                'message' => 'Animal not found.',
            ], 404);
        }

        if ((bool) $animal->is_for_sale) {
            return response()->json([
                'status' => true,
                'message' => 'Animal is already listed for sale.',
                'data' => $this->transformAnimal($animal->load(['animalType', 'pan', 'motherAnimal'])),
            ], 200);
        }

        $animal->update([
            'is_for_sale' => true,
            'selling_price' => round((float) $request->selling_price, 2),
            'listed_for_sale_at' => now(),
        ]);

        $animalLabel = trim(($animal->animal_name ?: 'Animal').' (Tag: '.($animal->tag_number ?: '-').')');
        $animalType = optional($animal->animalType)->name ?: 'Animal';

        Farmer::query()
            ->where('id', '!=', (int) $request->farmer_id)
            ->whereNotNull('fcm_token')
            ->chunkById(100, function ($farmers) use ($animalLabel, $animal, $animalType) {
                foreach ($farmers as $farmer) {
                    $this->firebaseService->sendToDevice(
                        $farmer->fcm_token,
                        'Animal For Sale',
                        trim($animalLabel.' is available for selling.'),
                        [
                            'type' => 'animal_sell',
                            'event' => 'animal_listed_for_sale',
                            'animal_id' => (string) $animal->id,
                            'animal_name' => (string) ($animal->animal_name ?? ''),
                            'tag_number' => (string) ($animal->tag_number ?? ''),
                            'animal_type' => (string) $animalType,
                            'selling_price' => (string) ($animal->selling_price ?? ''),
                            'image' => (string) ($animal->image_url ?? ''),
                        ]
                    );
                }
            });

        return response()->json([
            'status' => true,
            'message' => 'Animal listed for sale and notification sent.',
            'data' => $this->transformAnimal($animal->fresh()->load(['animalType', 'pan', 'motherAnimal'])),
        ], 200);
    }

    public function cancelForSale(Request $request, $animalId)
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

        $animal = Animal::query()
            ->where('id', $animalId)
            ->where('farmer_id', $request->farmer_id)
            ->first();

        if (! $animal) {
            return response()->json([
                'status' => false,
                'message' => 'Animal not found.',
            ], 404);
        }

        if (! (bool) $animal->is_for_sale) {
            return response()->json([
                'status' => true,
                'message' => 'Animal is not listed for sale.',
                'data' => $this->transformAnimal($animal->load(['animalType', 'pan', 'motherAnimal'])),
            ], 200);
        }

        $animal->update([
            'is_for_sale' => false,
            'selling_price' => null,
            'listed_for_sale_at' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Animal selling cancelled successfully.',
            'data' => $this->transformAnimal($animal->fresh()->load(['animalType', 'pan', 'motherAnimal'])),
        ], 200);
    }

    public function forSaleList()
    {
        $animals = Animal::query()
            ->with(['animalType', 'pan', 'motherAnimal'])
            ->where('is_for_sale', true)
            ->latest('listed_for_sale_at')
            ->latest('id')
            ->take(30)
            ->get()
            ->map(fn (Animal $animal) => $this->transformAnimal($animal));

        return response()->json([
            'status' => true,
            'message' => 'Animals for sale fetched successfully.',
            'data' => $animals,
        ]);
    }

    public function updateLifecycle(Request $request, $animalId)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:active,sold,death,move_type,move_pan',
            'animal_type_id' => 'nullable|required_if:action,move_type,move_pan|exists:animal_types,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $animal = Animal::with('animalType')->findOrFail($animalId);
        $previousStatus = $animal->lifecycle_status;
        $previousTypeId = $animal->animal_type_id;
        $now = now();
        $action = $request->action;

        if ($action === 'sold') {
            $animal->update([
                'lifecycle_status' => 'sold',
                'is_active' => false,
                'is_for_sale' => false,
                'selling_price' => null,
                'listed_for_sale_at' => null,
                'sold_at' => $now,
            ]);
        } elseif ($action === 'death') {
            $animal->update([
                'lifecycle_status' => 'death',
                'is_active' => false,
                'is_for_sale' => false,
                'selling_price' => null,
                'listed_for_sale_at' => null,
                'death_at' => $now,
            ]);
        } elseif ($action === 'active') {
            $animal->update([
                'lifecycle_status' => 'active',
                'is_active' => true,
            ]);
        } elseif (in_array($action, ['move_type', 'move_pan'], true)) {
            $animal->update([
                'animal_type_id' => (int) $request->animal_type_id,
                'lifecycle_status' => 'active',
                'is_active' => true,
            ]);
        }

        AnimalLifecycleHistory::create([
            'animal_id' => $animal->id,
            'action_type' => $action,
            'from_status' => $previousStatus,
            'to_status' => $animal->fresh()->lifecycle_status,
            'from_animal_type_id' => $previousTypeId,
            'to_animal_type_id' => in_array($action, ['move_type', 'move_pan'], true) ? (int) $request->animal_type_id : $previousTypeId,
            'notes' => $request->notes,
            'changed_at' => $now,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Animal lifecycle updated successfully',
            'data' => $this->transformAnimal($animal->fresh()->load('animalType')),
        ]);
    }

    public function history($farmer_id)
    {
        $history = AnimalLifecycleHistory::with(['animal.farmer', 'animal.animalType', 'fromAnimalType', 'toAnimalType', 'fromPan', 'toPan'])
            ->whereHas('animal', fn ($query) => $query->where('farmer_id', $farmer_id))
            ->latest('changed_at')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'animal_id' => $item->animal_id,
                    'animal_name' => $item->animal->animal_name ?? '-',
                    'tag_number' => $item->animal->tag_number ?? '-',
                    'action_type' => $item->action_type,
                    'from_status' => $item->from_status,
                    'to_status' => $item->to_status,
                    'from_animal_type' => $item->fromAnimalType->name ?? null,
                    'to_animal_type' => $item->toAnimalType->name ?? null,
                    'from_pan' => $item->fromPan->name ?? null,
                    'to_pan' => $item->toPan->name ?? null,
                    'notes' => $item->notes,
                    'changed_at' => optional($item->changed_at)->format('d-m-Y H:i'),
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Animal lifecycle history fetched successfully',
            'data' => $history,
        ]);
    }

    private function logPanTransferForAnimal(Animal $animal, ?int $fromPanId, ?int $toPanId, ?string $notes = null): void
    {
        if ($fromPanId === $toPanId) {
            return;
        }

        AnimalLifecycleHistory::create([
            'animal_id' => $animal->id,
            'action_type' => 'move_pan',
            'from_status' => $animal->lifecycle_status ?? 'active',
            'to_status' => $animal->lifecycle_status ?? 'active',
            'from_animal_type_id' => $animal->animal_type_id,
            'to_animal_type_id' => $animal->animal_type_id,
            'from_pan_id' => $fromPanId,
            'to_pan_id' => $toPanId,
            'notes' => blank($notes) ? 'PAN transfer recorded.' : $notes,
            'changed_at' => now(),
        ]);
    }

    private function transformAnimal($animal): array
    {
        return [
            'id' => $animal->id,
            'farmer_id' => $animal->farmer_id,
            'unique_id' => $animal->unique_id,
            'animal_name' => $animal->animal_name,
            'tag_number' => $animal->tag_number,
            'animal_type_id' => $animal->animal_type_id,
            'animal_type_name' => optional($animal->animalType)->name,
            'pan_id' => $animal->pan_id,
            'pan_name' => optional($animal->pan)->name,
            'pan_milk_shifts' => $this->normalizeMilkShifts(
                optional($animal->pan)->milk_shifts ?? [],
                allowEmpty: $this->normalizePanType(optional($animal->pan)->pan_type) === 'non_milking'
            ),
            'mother_animal_id' => $animal->mother_animal_id,
            'mother_animal_name' => optional($animal->motherAnimal)->animal_name,
            'mother_tag_number' => optional($animal->motherAnimal)->tag_number,
            'lactation_number' => $animal->lactation_number !== null ? (int) $animal->lactation_number : null,
            'ai_date' => $animal->ai_date ? Carbon::parse($animal->ai_date)->format('d/m/Y') : null,
            'breed_name' => $animal->breed_name,
            'age' => $animal->calculated_age,
            'age_display' => $animal->formatted_age,
            'birth_date' => $animal->birth_date ? Carbon::parse($animal->birth_date)->format('d/m/Y') : null,
            'purchase_date' => $animal->purchase_date ? Carbon::parse($animal->purchase_date)->format('d/m/Y') : null,
            'gender' => $animal->gender,
            'weight' => $animal->weight,
            'default_milk_per_session' => $animal->default_milk_per_session,
            'lifecycle_status' => $animal->lifecycle_status ?? 'active',
            'is_active' => (bool) $animal->is_active,
            'is_for_sale' => (bool) ($animal->is_for_sale ?? false),
            'selling_price' => $animal->selling_price !== null ? (float) $animal->selling_price : null,
            'listed_for_sale_at' => optional($animal->listed_for_sale_at)->toDateTimeString(),
            'daily_milk_production' => $animal->milkProductions()
                ->latest('date')
                ->latest('id')
                ->value('total_milk'),
            'image' => $animal->image_url,
        ];
    }

    private function normalizeMilkShifts($value, bool $allowEmpty = false): array
    {
        $allowed = ['Morning', 'Afternoon', 'Evening'];
        $items = is_array($value) ? $value : [];
        $normalized = collect($items)
            ->map(fn ($item) => ucfirst(strtolower(trim((string) $item))))
            ->filter(fn ($item) => in_array($item, $allowed, true))
            ->unique()
            ->values()
            ->all();

        if (empty($normalized)) {
            return $allowEmpty ? [] : $allowed;
        }
        return array_values(array_intersect($allowed, $normalized));
    }

    private function normalizePanType($value): string
    {
        $type = strtolower(trim((string) $value));
        return $type === 'non_milking' ? 'non_milking' : 'milking';
    }

    private function isMilkingAnimalType($typeName): bool
    {
        $type = strtolower(trim((string) $typeName));
        $hasMilking = str_contains($type, 'milking') || str_contains($type, 'milk');
        $hasNonMilking = str_contains($type, 'non-milking') ||
            str_contains($type, 'non milking') ||
            str_contains($type, 'dry');
        return $hasMilking && ! $hasNonMilking;
    }
}

