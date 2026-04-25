<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Animal;
use App\Models\Farmer\AnimalLifecycleHistory;
use App\Models\Farmer\FarmerPan;
use App\Models\Farmer\AnimalType;
use App\Models\Farmer\Farmer;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnimalListController extends Controller
{
    public function index()
    {
        $animals = Animal::with(['farmer', 'animalType', 'pan'])->latest()->get();
        $history = AnimalLifecycleHistory::with(['animal.farmer', 'fromAnimalType', 'toAnimalType', 'fromPan', 'toPan'])->latest('changed_at')->get();
        $animalTypes = AnimalType::all();
        $pans = FarmerPan::query()
            ->with(['farmer', 'animals'])
            ->latest()
            ->get();

        $counts = [
            'calf' => Animal::where('is_active', true)->whereHas('animalType', fn ($query) => $query->where('name', 'Calf'))->count(),
            'heifer' => Animal::where('is_active', true)->whereHas('animalType', fn ($query) => $query->where('name', 'Heifer'))->count(),
            'dry' => Animal::where('is_active', true)->whereHas('animalType', fn ($query) => $query->where('name', 'Dry Cow'))->count(),
            'milking' => Animal::where('is_active', true)->whereHas('animalType', fn ($query) => $query->where('name', 'Milking Cow'))->count(),
        ];

        return view('animal.index', compact('animals', 'counts', 'animalTypes', 'history', 'pans'));
    }

    public function create()
    {
        return view('animal.form', [
            'animal' => new Animal(),
            'farmers' => Farmer::orderBy('first_name')->get(),
            'animalTypes' => AnimalType::orderBy('name')->get(),
            'formTitle' => 'Add Animal',
            'formAction' => route('animal.store'),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'animal_name' => 'required|string|max:255',
            'tag_number' => 'required|string|max:255',
            'animal_type_id' => 'required|exists:animal_types,id',
            'birth_date' => 'required|date',
            'gender' => 'required|string|max:50',
            'weight' => 'nullable|numeric',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $animalTypeName = AnimalType::find($data['animal_type_id'])->name ?? 'XX';
        $animalTypeCode = strtoupper(substr($animalTypeName, 0, 2));
        $prefix = "C/$animalTypeCode";
        $lastAnimal = Animal::where('unique_id', 'like', "$prefix/%")->latest('id')->first();
        $nextNumber = $lastAnimal ? str_pad(((int) substr($lastAnimal->unique_id, -3)) + 1, 3, '0', STR_PAD_LEFT) : '001';
        $imagePath = $this->storeImage($request);

        Animal::create([
            'farmer_id' => $data['farmer_id'],
            'unique_id' => "$prefix/$nextNumber",
            'animal_name' => $data['animal_name'],
            'tag_number' => $data['tag_number'],
            'animal_type_id' => $data['animal_type_id'],
            'age' => Carbon::parse($data['birth_date'])->age,
            'birth_date' => $data['birth_date'],
            'gender' => $data['gender'],
            'weight' => $data['weight'],
            'image' => $imagePath,
            'lifecycle_status' => 'active',
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('farmer.animals')->with('success', 'Animal added successfully.');
    }

    public function edit(Animal $animal)
    {
        return view('animal.form', [
            'animal' => $animal,
            'farmers' => Farmer::orderBy('first_name')->get(),
            'animalTypes' => AnimalType::orderBy('name')->get(),
            'formTitle' => 'Edit Animal',
            'formAction' => route('animal.update', $animal),
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, Animal $animal)
    {
        $data = $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'animal_name' => 'required|string|max:255',
            'tag_number' => 'required|string|max:255',
            'animal_type_id' => 'required|exists:animal_types,id',
            'birth_date' => 'required|date',
            'gender' => 'required|string|max:50',
            'weight' => 'nullable|numeric',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $nextActive = $request->boolean('is_active');

        $animal->update([
            'farmer_id' => $data['farmer_id'],
            'animal_name' => $data['animal_name'],
            'tag_number' => $data['tag_number'],
            'animal_type_id' => $data['animal_type_id'],
            'age' => Carbon::parse($data['birth_date'])->age,
            'birth_date' => $data['birth_date'],
            'gender' => $data['gender'],
            'weight' => $data['weight'],
            'image' => $this->storeImage($request, $animal->image),
            'is_active' => $nextActive,
            'lifecycle_status' => $nextActive ? 'active' : 'inactive',
        ]);

        return redirect()->route('farmer.animals')->with('success', 'Animal updated successfully.');
    }

    public function toggle(Animal $animal)
    {
        $nextActive = ! $animal->is_active;

        $animal->update([
            'is_active' => $nextActive,
            'lifecycle_status' => $nextActive ? 'active' : 'inactive',
        ]);

        return redirect()->route('farmer.animals')->with('success', 'Animal status updated successfully.');
    }

    private function storeImage(Request $request, ?string $currentImage = null): ?string
    {
        if (! $request->hasFile('image')) {
            return $currentImage;
        }

        $directory = public_path('assets/animal_images');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file = $request->file('image');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'assets/animal_images/' . $filename;
    }
}
