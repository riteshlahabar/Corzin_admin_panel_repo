<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Farmer\Animal;
use App\Models\Farmer\AnimalType;
use App\Models\Farmer\AnimalLifecycleHistory;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AnimalController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'animal_name' => 'required|string|max:255',
            'tag_number' => 'required|string|max:255',
            'animal_type_id' => 'required|exists:animal_types,id',
            'birth_date' => 'required|date_format:d/m/Y',
            'gender' => 'required|string',
            'weight' => 'nullable|numeric',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,jfif|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $birthDateObj = Carbon::createFromFormat('d/m/Y', $request->birth_date);
        $birthDate = $birthDateObj->format('Y-m-d');
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
            'age' => $age,
            'birth_date' => $birthDate,
            'gender' => $request->gender,
            'weight' => $request->weight,
            'image' => $imagePath,
            'lifecycle_status' => 'active',
            'is_active' => true,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Animal created successfully',
            'data' => $this->transformAnimal($animal->load('animalType')),
        ]);
    }

    public function listByFarmer(Request $request, $farmer_id)
    {
        try {
            $query = Animal::with('animalType')->where('farmer_id', $farmer_id);

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

    public function update(Request $request, $animal_id)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'animal_name' => 'required|string|max:255',
            'tag_number' => 'required|string|max:255',
            'animal_type_id' => 'required|exists:animal_types,id',
            'birth_date' => 'required|date_format:d/m/Y',
            'gender' => 'required|string',
            'weight' => 'nullable|numeric',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,jfif|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
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
            'age' => $age,
            'birth_date' => $birthDate,
            'gender' => $request->gender,
            'weight' => $request->weight,
            'image' => $imagePath,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Animal updated successfully',
            'data' => $this->transformAnimal($animal->fresh()->load('animalType')),
        ], 200);
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
                'sold_at' => $now,
            ]);
        } elseif ($action === 'death') {
            $animal->update([
                'lifecycle_status' => 'death',
                'is_active' => false,
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
        $history = AnimalLifecycleHistory::with(['animal.farmer', 'animal.animalType', 'fromAnimalType', 'toAnimalType'])
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

    private function transformAnimal($animal): array
    {
        return [
            'id' => $animal->id,
            'unique_id' => $animal->unique_id,
            'animal_name' => $animal->animal_name,
            'tag_number' => $animal->tag_number,
            'animal_type_id' => $animal->animal_type_id,
            'animal_type_name' => optional($animal->animalType)->name,
            'age' => $animal->calculated_age,
            'birth_date' => $animal->birth_date ? Carbon::parse($animal->birth_date)->format('d/m/Y') : null,
            'gender' => $animal->gender,
            'weight' => $animal->weight,
            'lifecycle_status' => $animal->lifecycle_status ?? 'active',
            'is_active' => (bool) $animal->is_active,
            'image' => $animal->image_url,
        ];
    }
}

