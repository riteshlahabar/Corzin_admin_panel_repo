<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Animal;
use App\Models\Farmer\AnimalLifecycleHistory;
use App\Models\Farmer\AnimalPregnancy;
use App\Models\Farmer\FarmerPan;
use App\Models\Farmer\AnimalType;
use App\Models\Farmer\Farmer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class AnimalListController extends Controller
{
    public function index()
    {
        $animals = Animal::with(['farmer', 'animalType', 'pan'])->latest()->get();
        $history = AnimalLifecycleHistory::with(['animal.farmer', 'fromAnimalType', 'toAnimalType', 'fromPan', 'toPan'])->latest('changed_at')->get();
        $animalTypes = AnimalType::orderBy('name')->get();
        $typeCounts = Animal::query()
            ->where('is_active', true)
            ->selectRaw('animal_type_id, COUNT(*) as total')
            ->groupBy('animal_type_id')
            ->pluck('total', 'animal_type_id');

        return view('animal.index', compact('animals', 'typeCounts', 'animalTypes', 'history'));
    }

    public function panList()
    {
        $pans = FarmerPan::query()
            ->with(['farmer', 'animals.animalType'])
            ->latest()
            ->get();

        $farmers = Farmer::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $assignableAnimals = Animal::query()
            ->with(['farmer', 'animalType'])
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('pan_id')->orWhere('pan_id', 0);
            })
            ->orderBy('animal_name')
            ->get();

        return view('animal.pan_list', compact('pans', 'farmers', 'assignableAnimals'));
    }

    public function storePan(Request $request)
    {
        $data = $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'name' => 'required|string|max:255',
            'pan_type' => 'nullable|in:milking,non_milking',
            'milk_shifts' => 'nullable|array',
            'milk_shifts.*' => 'in:Morning,Afternoon,Evening',
            'animal_ids' => 'nullable|array',
            'animal_ids.*' => 'integer|exists:animals,id',
        ]);

        $farmerId = (int) $data['farmer_id'];
        $panType = ($data['pan_type'] ?? 'milking') === 'non_milking' ? 'non_milking' : 'milking';
        $milkShifts = $panType === 'non_milking'
            ? []
            : collect($data['milk_shifts'] ?? ['Morning', 'Afternoon', 'Evening'])
                ->map(fn($item) => trim((string) $item))
                ->filter(fn($item) => in_array($item, ['Morning', 'Afternoon', 'Evening'], true))
                ->unique()
                ->values()
                ->all();

        if ($panType === 'milking' && empty($milkShifts)) {
            return redirect()
                ->route('farmer.pans')
                ->withErrors(['milk_shifts' => 'Please select at least one milk shift for Milking Pen.'])
                ->withInput();
        }

        $animalIds = collect($data['animal_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($animalIds->isNotEmpty()) {
            $selectedAnimals = Animal::query()
                ->with('animalType')
                ->where('farmer_id', $farmerId)
                ->whereIn('id', $animalIds)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('pan_id')->orWhere('pan_id', 0);
                })
                ->get();

            $invalidAnimals = $selectedAnimals->filter(function (Animal $animal) use ($panType) {
                $isMilking = $this->isMilkingAnimalTypeName((string) optional($animal->animalType)->name);
                return $panType === 'milking' ? !$isMilking : $isMilking;
            });

            if ($selectedAnimals->count() !== $animalIds->count() || $invalidAnimals->isNotEmpty()) {
                return redirect()
                    ->route('farmer.pans')
                    ->withErrors([
                        'animal_ids' => $panType === 'milking'
                            ? 'Please select only milking cows that are not already assigned to another Pen.'
                            : 'Please select only non-milking cows that are not already assigned to another Pen.',
                    ])
                    ->withInput();
            }
        }

        try {
            DB::transaction(function () use ($data, $farmerId, $panType, $milkShifts, $animalIds) {
                $pan = FarmerPan::query()->create([
                    'farmer_id' => $farmerId,
                    'name' => trim((string) $data['name']),
                    'pan_type' => $panType,
                    'milk_shifts' => $milkShifts,
                ]);

                if ($animalIds->isEmpty()) {
                    return;
                }

                $animals = Animal::query()
                    ->with('animalType')
                    ->where('farmer_id', $farmerId)
                    ->whereIn('id', $animalIds)
                    ->where('is_active', true)
                    ->get();

                foreach ($animals as $animal) {
                    $fromPanId = $animal->pan_id;
                    if ($fromPanId === $pan->id) {
                        continue;
                    }

                    $animal->update(['pan_id' => $pan->id]);
                    $this->logPanTransferHistory($animal->fresh(), $fromPanId, $pan->id, 'Assigned in admin Pen creation.');
                }
            });
        } catch (\Throwable $exception) {
            return redirect()
                ->route('farmer.pans')
                ->with('error', 'Failed to create Pen. ' . $exception->getMessage());
        }

        return redirect()->route('farmer.pans')->with('success', 'Pen created successfully.');
    }

    public function transferPanAnimal(Request $request)
    {
        $data = $request->validate([
            'animal_id' => 'required|integer|exists:animals,id',
            'to_pan_id' => 'required|integer|exists:farmer_pans,id',
            'notes' => 'nullable|string|max:255',
        ]);

        $animal = Animal::query()
            ->with(['animalType', 'farmer'])
            ->findOrFail((int) $data['animal_id']);

        $toPan = FarmerPan::query()
            ->where('id', (int) $data['to_pan_id'])
            ->where('farmer_id', $animal->farmer_id)
            ->first();

        if (!$toPan) {
            return redirect()->route('farmer.pans')->with('error', 'Destination Pen not found for this farmer.');
        }

        if ((int) $animal->pan_id === (int) $toPan->id) {
            return redirect()->route('farmer.pans')->with('success', 'Animal is already in selected Pen.');
        }

        $fromPanId = $animal->pan_id;
        $animal->update(['pan_id' => $toPan->id]);
        $this->logPanTransferHistory(
            $animal->fresh(),
            $fromPanId,
            $toPan->id,
            $data['notes'] ?? 'Transferred from admin Pen list.'
        );

        return redirect()->route('farmer.pans')->with('success', 'Animal transferred successfully.');
    }

    public function destroyPan(FarmerPan $pan)
    {
        if ($pan->animals()->count() > 0) {
            return redirect()
                ->route('farmer.pans')
                ->with('error', 'Cannot delete Pen while animals are assigned. Please transfer animals first.');
        }

        $hasPanMilkEntries = DB::table('pan_milk_entries')
            ->where('pan_id', $pan->id)
            ->exists();

        if ($hasPanMilkEntries) {
            return redirect()
                ->route('farmer.pans')
                ->with('error', 'Cannot delete Pen with milk records.');
        }

        $pan->delete();

        return redirect()->route('farmer.pans')->with('success', 'Pen deleted successfully.');
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

    public function downloadImportTemplate(): StreamedResponse
    {
        $headers = [
            'farmer_mobile',
            'farmer_name',
            'animal_name',
            'tag_number',
            'animal_type_name',
            'lactation_number',
            'ai_date',
            'breed_name',
            'birth_date',
            'purchase_date',
            'gender',
            'weight',
            'default_milk_per_session',
            'is_active',
            'pregnancy_status',
            'pregnancy_service_type',
            'pregnancy_check_due_date',
            'pregnancy_check_date',
            'delivery_date',
            'abort_date',
            'abort_reason',
            'pregnancy_notes',
        ];

        $sampleRows = [
            [
                '9876543210',
                'Ritesh Deshmukh',
                'Rani',
                'TAG1001',
                'Milking Cows',
                '2',
                '15/05/2026',
                'HF',
                '10/01/2023',
                '12/01/2024',
                'Female',
                '450',
                '8.5',
                '1',
                'pregnant',
                'ai',
                '15/06/2026',
                '',
                '',
                '',
                '',
                'Imported pregnancy record',
            ],
            [
                '9876543210',
                'Ritesh Deshmukh',
                'Gauri',
                'TAG1002',
                'Dry Cows',
                '1',
                '',
                'Jersey',
                '08/03/2022',
                '15/03/2023',
                'Female',
                '390',
                '',
                '1',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ],
        ];

        $filename = 'animal_import_template.csv';
        return response()->streamDownload(function () use ($headers, $sampleRows) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);
            foreach ($sampleRows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importAnimals(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xls,xlsx|max:10240',
        ]);

        $file = $request->file('file');
        $extension = Str::lower($file->getClientOriginalExtension() ?: '');

        try {
            $rows = match ($extension) {
                'csv', 'txt' => $this->readCsvRows($file->getRealPath()),
                'xlsx' => $this->readXlsxRows($file->getRealPath()),
                'xls' => throw new \RuntimeException('Old .xls files are not supported yet. Please save the sheet as .xlsx or CSV and upload again.'),
                default => throw new \RuntimeException('Unsupported import file format. Please upload CSV or Excel (.xlsx).'),
            };
        } catch (\Throwable $exception) {
            return redirect()
                ->route('farmer.animals')
                ->with('error', $exception->getMessage());
        }

        if (empty($rows)) {
            return redirect()->route('farmer.animals')->with('error', 'Unable to read import file.');
        }

        $header = array_shift($rows);
        if (!is_array($header) || empty($header)) {
            return redirect()->route('farmer.animals')->with('error', 'Invalid template format.');
        }

        $headerMap = collect($header)
            ->map(fn($item) => Str::lower(ltrim(trim((string) $item), "\xEF\xBB\xBF")))
            ->values()
            ->all();

        $requiredColumns = ['animal_name', 'tag_number', 'birth_date'];
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $headerMap, true)) {
                return redirect()
                    ->route('farmer.animals')
                    ->with('error', "Missing required column in file: {$column}");
            }
        }

        $created = 0;
        $errors = [];
        $rowNo = 1;

        foreach ($rows as $row) {
            $rowNo++;
            if (!is_array($row) || $this->isImportRowBlank($row)) {
                continue;
            }

            $payload = [];
            foreach ($headerMap as $index => $column) {
                $payload[$column] = trim((string) ($row[$index] ?? ''));
            }

            $result = $this->importAnimalRow($payload, $rowNo);
            if ($result['ok']) {
                $created++;
            } else {
                $errors[] = $result['error'];
            }
        }

        if ($created === 0 && count($errors) > 0) {
            return redirect()
                ->route('farmer.animals')
                ->with('error', 'No animals imported. Please check file rows.')
                ->with('import_errors', $errors);
        }

        return redirect()
            ->route('farmer.animals')
            ->with('success', "Animal import completed. Created: {$created}")
            ->with('import_errors', $errors);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'animal_name' => 'required|string|max:255',
            'tag_number' => 'required|string|max:255',
            'animal_type_id' => 'required|exists:animal_types,id',
            'lactation_number' => 'nullable|integer|min:0',
            'ai_date' => 'nullable|date',
            'breed_name' => 'nullable|string|max:255',
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
            'lactation_number' => $data['lactation_number'] ?? null,
            'ai_date' => $data['ai_date'] ?? null,
            'breed_name' => $data['breed_name'] ?? null,
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
            'lactation_number' => 'nullable|integer|min:0',
            'ai_date' => 'nullable|date',
            'breed_name' => 'nullable|string|max:255',
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
            'lactation_number' => $data['lactation_number'] ?? null,
            'ai_date' => $data['ai_date'] ?? null,
            'breed_name' => $data['breed_name'] ?? null,
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
        $nextActive = !$animal->is_active;

        $animal->update([
            'is_active' => $nextActive,
            'lifecycle_status' => $nextActive ? 'active' : 'inactive',
        ]);

        return redirect()->route('farmer.animals')->with('success', 'Animal status updated successfully.');
    }

    private function storeImage(Request $request, ?string $currentImage = null): ?string
    {
        if (!$request->hasFile('image')) {
            return $currentImage;
        }

        $directory = public_path('assets/animal_images');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file = $request->file('image');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'assets/animal_images/' . $filename;
    }

    private function importAnimalRow(array $payload, int $rowNo): array
    {
        $farmer = $this->resolveFarmerForImport($payload);
        if (!$farmer) {
            return ['ok' => false, 'error' => "Row {$rowNo}: Farmer not found (use farmer_id, farmer_mobile, or exact farmer_name)."];
        }

        $animalType = $this->resolveAnimalTypeForImport($payload);
        if (!$animalType) {
            return ['ok' => false, 'error' => "Row {$rowNo}: Animal type not found (use animal_type_id or animal_type_name)."];
        }

        $normalized = [
            'farmer_id' => $farmer->id,
            'animal_name' => $payload['animal_name'] ?? '',
            'tag_number' => $payload['tag_number'] ?? '',
            'animal_type_id' => $animalType->id,
            'lactation_number' => $this->toNullableInt($payload['lactation_number'] ?? null),
            'ai_date' => $this->parseDateValue($payload['ai_date'] ?? null),
            'breed_name' => $this->toNullableString($payload['breed_name'] ?? null),
            'birth_date' => $this->parseDateValue($payload['birth_date'] ?? null),
            'purchase_date' => $this->parseDateValue($payload['purchase_date'] ?? null),
            'gender' => $this->normalizeGender($payload['gender'] ?? ''),
            'weight' => $this->toNullableFloat($payload['weight'] ?? null),
            'default_milk_per_session' => $this->toNullableFloat($payload['default_milk_per_session'] ?? null),
            'is_active' => $this->toBool($payload['is_active'] ?? '1'),
        ];

        $validator = Validator::make($normalized, [
            'farmer_id' => 'required|exists:farmers,id',
            'animal_name' => 'required|string|max:255',
            'tag_number' => 'required|string|max:255',
            'animal_type_id' => 'required|exists:animal_types,id',
            'lactation_number' => 'nullable|integer|min:0',
            'ai_date' => 'nullable|date',
            'breed_name' => 'nullable|string|max:255',
            'birth_date' => 'required|date',
            'purchase_date' => 'nullable|date',
            'gender' => 'required|string|max:50',
            'weight' => 'nullable|numeric|min:0',
            'default_milk_per_session' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return [
                'ok' => false,
                'error' => "Row {$rowNo}: " . collect($validator->errors()->all())->implode(' | '),
            ];
        }

        $duplicateTag = Animal::query()
            ->where('farmer_id', $farmer->id)
            ->whereRaw('LOWER(tag_number) = ?', [Str::lower($normalized['tag_number'])])
            ->exists();
        if ($duplicateTag) {
            return ['ok' => false, 'error' => "Row {$rowNo}: Tag number already exists for this farmer."];
        }

        $pregnancyImport = $this->buildPregnancyImportPayload($payload, $normalized, $rowNo);
        if (!$pregnancyImport['ok']) {
            return ['ok' => false, 'error' => $pregnancyImport['error']];
        }

        try {
            DB::transaction(function () use ($animalType, $normalized, $pregnancyImport) {
                $animalTypeName = $animalType->name ?? 'XX';
                $animalTypeCode = strtoupper(substr($animalTypeName, 0, 2));
                $prefix = "C/$animalTypeCode";
                $lastAnimal = Animal::query()
                    ->where('unique_id', 'like', "$prefix/%")
                    ->latest('id')
                    ->first();
                $nextNumber = $lastAnimal
                    ? str_pad(((int) substr((string) $lastAnimal->unique_id, -3)) + 1, 3, '0', STR_PAD_LEFT)
                    : '001';

                $animal = Animal::create([
                    'farmer_id' => $normalized['farmer_id'],
                    'unique_id' => "$prefix/$nextNumber",
                    'animal_name' => $normalized['animal_name'],
                    'tag_number' => $normalized['tag_number'],
                    'animal_type_id' => $normalized['animal_type_id'],
                    'lactation_number' => $normalized['lactation_number'],
                    'ai_date' => $normalized['ai_date'],
                    'breed_name' => $normalized['breed_name'],
                    'age' => Carbon::parse($normalized['birth_date'])->age,
                    'birth_date' => $normalized['birth_date'],
                    'purchase_date' => $normalized['purchase_date'],
                    'gender' => $normalized['gender'],
                    'weight' => $normalized['weight'],
                    'default_milk_per_session' => $normalized['default_milk_per_session'],
                    'lifecycle_status' => $normalized['is_active'] ? 'active' : 'inactive',
                    'is_active' => $normalized['is_active'],
                    'is_for_sale' => false,
                ]);

                if (is_array($pregnancyImport['data'])) {
                    $this->createImportedPregnancyRecord($animal, $pregnancyImport['data']);
                }
            });
        } catch (\Throwable $exception) {
            return ['ok' => false, 'error' => "Row {$rowNo}: " . $exception->getMessage()];
        }

        return ['ok' => true];
    }

    private function resolveFarmerForImport(array $payload): ?Farmer
    {
        $farmerId = $this->toNullableInt($payload['farmer_id'] ?? null);
        if ($farmerId && $farmerId > 0) {
            return Farmer::query()->find($farmerId);
        }

        $mobile = preg_replace('/\D+/', '', (string) ($payload['farmer_mobile'] ?? ''));
        if (!blank($mobile)) {
            return Farmer::query()->where('mobile', $mobile)->first();
        }

        $name = $this->normalizeImportName($payload['farmer_name'] ?? '');
        if ($name !== '') {
            return Farmer::query()
                ->get()
                ->first(function (Farmer $farmer) use ($name) {
                    return $this->normalizeImportName(implode(' ', array_filter([
                        $farmer->first_name,
                        $farmer->middle_name,
                        $farmer->last_name,
                    ]))) === $name;
                });
        }

        return null;
    }

    private function resolveAnimalTypeForImport(array $payload): ?AnimalType
    {
        $animalTypeId = $this->toNullableInt($payload['animal_type_id'] ?? null);
        if ($animalTypeId && $animalTypeId > 0) {
            $type = AnimalType::query()->find($animalTypeId);
            if ($type) {
                return $type;
            }
        }

        $name = trim((string) ($payload['animal_type_name'] ?? ''));
        if ($name !== '') {
            return AnimalType::query()
                ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
                ->first();
        }

        return null;
    }

    private function parseDateValue(?string $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (is_numeric($raw)) {
            $excelSerial = (float) $raw;
            if ($excelSerial > 59 && $excelSerial < 100000) {
                try {
                    return Carbon::create(1899, 12, 30, 0, 0, 0)
                        ->addDays((int) floor($excelSerial))
                        ->format('Y-m-d');
                } catch (\Throwable) {
                    // continue
                }
            }
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'm-d-Y'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $raw)->format('Y-m-d');
            } catch (\Throwable) {
                // continue
            }
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeImportName(?string $value): string
    {
        $value = Str::lower(trim((string) $value));
        if ($value === '') {
            return '';
        }

        return preg_replace('/\s+/', ' ', $value) ?? '';
    }

    private function toNullableString($value): ?string
    {
        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }

    private function toNullableInt($value): ?int
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }
        if (!is_numeric($text)) {
            return null;
        }
        return (int) $text;
    }

    private function toNullableFloat($value): ?float
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }
        if (!is_numeric($text)) {
            return null;
        }
        return (float) $text;
    }

    private function toBool($value): bool
    {
        $text = Str::lower(trim((string) $value));
        return in_array($text, ['1', 'true', 'yes', 'active'], true);
    }

    private function normalizeGender(?string $value): string
    {
        $text = Str::lower(trim((string) $value));
        if ($text === 'male' || $text === 'm') {
            return 'Male';
        }
        return 'Female';
    }

    private function isImportRowBlank(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }
        return true;
    }

    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new \RuntimeException('Unable to read import file.');
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    private function readXlsxRows(string $path): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new \RuntimeException('Zip extension is not available for Excel import.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Unable to open Excel file.');
        }

        try {
            $sheetPath = $this->resolveFirstWorksheetPath($zip);
            if (!$sheetPath) {
                throw new \RuntimeException('Worksheet not found in Excel file.');
            }

            $sheetXml = $zip->getFromName($sheetPath);
            if ($sheetXml === false) {
                throw new \RuntimeException('Unable to read worksheet data from Excel file.');
            }

            return $this->parseXlsxSheetRows($sheetXml, $this->readXlsxSharedStrings($zip));
        } finally {
            $zip->close();
        }
    }

    private function resolveFirstWorksheetPath(ZipArchive $zip): ?string
    {
        $sheetPaths = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (is_string($name) && preg_match('#^xl/worksheets/sheet\\d+\\.xml$#i', $name)) {
                $sheetPaths[] = $name;
            }
        }

        if (empty($sheetPaths)) {
            return null;
        }

        sort($sheetPaths, SORT_NATURAL | SORT_FLAG_CASE);

        return $sheetPaths[0] ?? null;
    }

    private function readXlsxSharedStrings(ZipArchive $zip): array
    {
        $xmlString = $zip->getFromName('xl/sharedStrings.xml');
        if ($xmlString === false) {
            return [];
        }

        $xml = @simplexml_load_string($xmlString);
        if (!$xml) {
            return [];
        }

        $xml->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $items = $xml->xpath('//a:si') ?: [];

        $strings = [];
        foreach ($items as $item) {
            $item->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $texts = $item->xpath('.//a:t') ?: [];
            $strings[] = collect($texts)
                ->map(fn($textNode) => (string) $textNode)
                ->implode('');
        }

        return $strings;
    }

    private function parseXlsxSheetRows(string $sheetXml, array $sharedStrings): array
    {
        $xml = @simplexml_load_string($sheetXml);
        if (!$xml) {
            throw new \RuntimeException('Invalid worksheet XML in Excel file.');
        }

        $xml->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rowNodes = $xml->xpath('//a:sheetData/a:row') ?: [];

        $rows = [];
        foreach ($rowNodes as $rowNode) {
            $cells = [];
            $rowNode->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $cellNodes = $rowNode->xpath('./a:c') ?: [];

            foreach ($cellNodes as $cellNode) {
                $reference = (string) $cellNode['r'];
                $columnIndex = $this->xlsxColumnIndexFromReference($reference);
                $cells[$columnIndex] = $this->readXlsxCellValue($cellNode, $sharedStrings);
            }

            if (empty($cells)) {
                $rows[] = [];
                continue;
            }

            ksort($cells);
            $maxIndex = max(array_keys($cells));
            $row = array_fill(0, $maxIndex + 1, '');
            foreach ($cells as $columnIndex => $value) {
                $row[$columnIndex] = $value;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function xlsxColumnIndexFromReference(string $reference): int
    {
        $letters = preg_replace('/[^A-Z]/i', '', strtoupper($reference)) ?: 'A';
        $index = 0;

        foreach (str_split($letters) as $character) {
            $index = ($index * 26) + (ord($character) - 64);
        }

        return max($index - 1, 0);
    }

    private function readXlsxCellValue(\SimpleXMLElement $cellNode, array $sharedStrings): string
    {
        $cellNode->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $type = (string) $cellNode['t'];

        if ($type === 'inlineStr') {
            $texts = $cellNode->xpath('./a:is//a:t') ?: [];
            return collect($texts)
                ->map(fn($textNode) => (string) $textNode)
                ->implode('');
        }

        $value = isset($cellNode->v) ? (string) $cellNode->v : '';
        if ($type === 's') {
            return (string) ($sharedStrings[(int) $value] ?? '');
        }

        if ($type === 'b') {
            return $value == '1' ? '1' : '0';
        }

        return $value;
    }

    private function buildPregnancyImportPayload(array $payload, array $normalized, int $rowNo): array
    {
        $statusRaw = $payload['pregnancy_status'] ?? null;
        $serviceTypeRaw = $payload['pregnancy_service_type'] ?? null;
        $checkDueRaw = $payload['pregnancy_check_due_date'] ?? null;
        $checkDateRaw = $payload['pregnancy_check_date'] ?? null;
        $deliveryDateRaw = $payload['delivery_date'] ?? null;
        $abortDateRaw = $payload['abort_date'] ?? null;
        $abortReasonRaw = $payload['abort_reason'] ?? null;
        $notesRaw = $payload['pregnancy_notes'] ?? null;

        $hasPregnancyInput = collect([
            $statusRaw,
            $serviceTypeRaw,
            $checkDueRaw,
            $checkDateRaw,
            $deliveryDateRaw,
            $abortDateRaw,
            $abortReasonRaw,
            $notesRaw,
        ])->contains(fn($value) => $this->hasImportValue($value));

        if (!$hasPregnancyInput) {
            return ['ok' => true, 'data' => null];
        }

        if (blank($normalized['ai_date'])) {
            return ['ok' => false, 'error' => "Row {$rowNo}: AI date is required when pregnancy details are filled."];
        }

        $status = $this->normalizePregnancyStatus($statusRaw);

        if ($status === '__invalid__') {
            return ['ok' => false, 'error' => "Row {$rowNo}: Invalid pregnancy_status. Use pregnancy_check_due, pregnant, not_pregnant, repeat_heat, aborted, calved, delivery, or abort."];
        }

        $deliveryDate = $this->parseDateValue($deliveryDateRaw);
        if ($this->hasImportValue($deliveryDateRaw) && $deliveryDate === null) {
            return ['ok' => false, 'error' => "Row {$rowNo}: Invalid delivery_date. Use a valid date."];
        }

        $abortDate = $this->parseDateValue($abortDateRaw);
        if ($this->hasImportValue($abortDateRaw) && $abortDate === null) {
            return ['ok' => false, 'error' => "Row {$rowNo}: Invalid abort_date. Use a valid date."];
        }

        if ($deliveryDate && blank($statusRaw)) {
            $status = 'calved';
        }

        if ($abortDate && blank($statusRaw)) {
            $status = 'aborted';
        }

        $status ??= 'pregnancy_check_due';

        $serviceType = $this->normalizePregnancyServiceType($serviceTypeRaw);
        if ($serviceType === '__invalid__') {
            return ['ok' => false, 'error' => "Row {$rowNo}: Invalid pregnancy_service_type. Use ai or natural."];
        }
        $serviceType ??= 'ai';

        $checkDueDate = $this->parseDateValue($checkDueRaw);
        if ($this->hasImportValue($checkDueRaw) && $checkDueDate === null) {
            return ['ok' => false, 'error' => "Row {$rowNo}: Invalid pregnancy_check_due_date. Use a valid date."];
        }

        $checkDate = $this->parseDateValue($checkDateRaw);
        if ($this->hasImportValue($checkDateRaw) && $checkDate === null) {
            return ['ok' => false, 'error' => "Row {$rowNo}: Invalid pregnancy_check_date. Use a valid date."];
        }

        $aiDate = Carbon::parse($normalized['ai_date']);

        return [
            'ok' => true,
            'data' => [
                'status' => $status,
                'service_type' => $serviceType,
                'pregnancy_check_due_date' => $checkDueDate ?: $aiDate->copy()->addDays(30)->toDateString(),
                'pregnancy_check_date' => $checkDate,
                'pregnancy_result' => $this->pregnancyResultForStatus($status),
                'expected_calving_date' => $aiDate->copy()->addDays(283)->toDateString(),
                'dry_off_date' => $aiDate->copy()->addDays(223)->toDateString(),
                'calving_date' => $deliveryDate,
                'abort_date' => $abortDate,
                'abort_reason' => $this->toNullableString($abortReasonRaw),
                'notes' => $this->toNullableString($notesRaw),
                'is_current' => $this->isCurrentPregnancyStatus($status),
            ],
        ];
    }

    private function createImportedPregnancyRecord(Animal $animal, array $pregnancyData): void
    {
        if ($pregnancyData['is_current']) {
            $this->closeCurrentPregnancyRecords($animal->id);
        }

        $numbers = $this->nextPregnancyNumbers($animal->id);

        AnimalPregnancy::create([
            'farmer_id' => $animal->farmer_id,
            'animal_id' => $animal->id,
            'pregnancy_no' => $numbers['pregnancy_no'],
            'service_no' => $numbers['service_no'],
            'heat_date' => null,
            'ai_date' => $animal->ai_date,
            'service_type' => $pregnancyData['service_type'],
            'bull_name' => null,
            'semen_no' => null,
            'doctor_name' => null,
            'pregnancy_check_due_date' => $pregnancyData['pregnancy_check_due_date'],
            'pregnancy_check_date' => $pregnancyData['pregnancy_check_date'],
            'pregnancy_result' => $pregnancyData['pregnancy_result'],
            'expected_calving_date' => $pregnancyData['expected_calving_date'],
            'dry_off_date' => $pregnancyData['dry_off_date'],
            'calving_date' => $pregnancyData['calving_date'],
            'abort_date' => $pregnancyData['abort_date'],
            'abort_reason' => $pregnancyData['abort_reason'],
            'status' => $pregnancyData['status'],
            'calf_animal_id' => null,
            'notes' => $pregnancyData['notes'],
            'is_current' => $pregnancyData['is_current'],
        ]);
    }

    private function nextPregnancyNumbers(int $animalId): array
    {
        $latest = AnimalPregnancy::query()
            ->where('animal_id', $animalId)
            ->latest('pregnancy_no')
            ->latest('service_no')
            ->latest('id')
            ->first();

        if (!$latest) {
            return ['pregnancy_no' => 1, 'service_no' => 1];
        }

        if ($latest->status === 'calved') {
            return ['pregnancy_no' => (int) $latest->pregnancy_no + 1, 'service_no' => 1];
        }

        if (in_array($latest->status, ['not_pregnant', 'repeat_heat', 'aborted'], true)) {
            return ['pregnancy_no' => (int) $latest->pregnancy_no, 'service_no' => (int) $latest->service_no + 1];
        }

        return ['pregnancy_no' => (int) $latest->pregnancy_no, 'service_no' => (int) $latest->service_no + 1];
    }

    private function closeCurrentPregnancyRecords(int $animalId): void
    {
        AnimalPregnancy::query()
            ->where('animal_id', $animalId)
            ->where('is_current', true)
            ->update(['is_current' => false]);
    }

    private function pregnancyResultForStatus(string $status): string
    {
        return match ($status) {
            'pregnant', 'calved', 'aborted' => 'pregnant',
            'not_pregnant', 'repeat_heat' => 'not_pregnant',
            default => 'pending',
        };
    }

    private function isCurrentPregnancyStatus(string $status): bool
    {
        return in_array($status, ['served', 'pregnancy_check_due', 'pregnant'], true);
    }

    private function normalizePregnancyStatus(?string $value): ?string
    {
        $text = Str::of((string) $value)
            ->trim()
            ->lower()
            ->replace(['-', '_'], ' ')
            ->squish()
            ->value();

        return match ($text) {
            '', 'na', 'n/a', 'none', 'no' => null,
            'served' => 'served',
            'pregnancy check due', 'check due', 'due' => 'pregnancy_check_due',
            'pregnant' => 'pregnant',
            'not pregnant', 'notpregnant', 'non pregnant', 'nonpregnant' => 'not_pregnant',
            'repeat heat', 'repeatheat' => 'repeat_heat',
            'aborted', 'abortion', 'abort' => 'aborted',
            'calved', 'calving', 'delivery', 'delivered' => 'calved',
            default => '__invalid__',
        };
    }

    private function normalizePregnancyServiceType(?string $value): ?string
    {
        $text = Str::of((string) $value)
            ->trim()
            ->lower()
            ->replace(['-', '_'], ' ')
            ->squish()
            ->value();

        return match ($text) {
            '', 'na', 'n/a', 'none' => null,
            'ai', 'artificial', 'artificial insemination' => 'ai',
            'natural', 'natural service' => 'natural',
            default => '__invalid__',
        };
    }

    private function hasImportValue($value): bool
    {
        return trim((string) $value) !== '';
    }

    private function isMilkingAnimalTypeName(string $typeName): bool
    {
        $value = trim(strtolower($typeName));
        if ($value === '') {
            return true;
        }

        return str_contains($value, 'milking')
            || str_contains($value, 'milky')
            || str_contains($value, 'dudh')
            || str_contains($value, 'dugh')
            || str_contains($value, 'milk');
    }

    private function logPanTransferHistory(Animal $animal, ?int $fromPanId, ?int $toPanId, ?string $notes = null): void
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
            'notes' => $notes,
            'changed_at' => now(),
        ]);
    }
}
