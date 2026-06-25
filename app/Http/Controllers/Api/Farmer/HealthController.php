<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Animal;
use App\Models\Farmer\AnimalVaccination;
use App\Models\Farmer\DmiRecord;
use App\Models\Farmer\FeedDietPlan;
use App\Models\Farmer\FeedingRecord;
use App\Models\Farmer\MastitisRecord;
use App\Models\Farmer\MedicalRecord;
use App\Models\Farmer\MilkProduction;
use App\Models\Farmer\Vaccine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HealthController extends Controller
{
    public function vaccineList()
    {
        $rows = Vaccine::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Vaccine $row) => [
                'id' => $row->id,
                'name' => $row->name,
                'description' => $row->description,
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Vaccines fetched successfully',
            'data' => $rows,
        ]);
    }

    public function medicalList($farmerId)
    {
        $rows = MedicalRecord::with('animal')
            ->where('farmer_id', $farmerId)
            ->latest('date')
            ->get()
            ->map(fn($row) => [
                'id' => $row->id,
                'case_id' => $row->case_id ?: $row->id,
                'animal_id' => $row->animal_id,
                'animal_name' => $row->animal->animal_name ?? '-',
                'tag_number' => $row->animal->tag_number ?? '-',
                'medicine_name' => $row->medicine_name,
                'dose' => $row->dose,
                'date' => optional($row->date)->format('d/m/Y'),
                'disease' => $row->disease,
                'notes' => $row->notes,
            ]);

        return response()->json(['status' => true, 'message' => 'Medical records fetched successfully', 'data' => $rows]);
    }

    public function storeMedical(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'animal_id' => 'required|exists:animals,id',
            'medicine_name' => 'required|string|max:255',
            'dose' => 'required|string|max:255',
            'date' => 'required|date',
            'disease' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }

        $row = MedicalRecord::create($validator->validated());
        return response()->json(['status' => true, 'message' => 'Medical record saved successfully', 'data' => $row], 201);
    }

    public function vaccinationList($farmerId)
    {
        $rows = AnimalVaccination::with(['animal.animalType', 'vaccine'])
            ->where('farmer_id', $farmerId)
            ->latest('vaccination_date')
            ->latest('id')
            ->get()
            ->map(fn (AnimalVaccination $row) => [
                'id' => $row->id,
                'animal_id' => $row->animal_id,
                'animal_name' => $row->animal->animal_name ?? '-',
                'tag_number' => $row->animal->tag_number ?? '-',
                'animal_type_name' => optional(optional($row->animal)->animalType)->name ?? '',
                'pan_id' => (int) ($row->pan_id ?? 0),
                'pan_name' => $row->pan_name ?: (optional(optional($row->animal)->pan)->name ?? ''),
                'vaccine_id' => (int) $row->vaccine_id,
                'vaccine_name' => $row->vaccine->name ?? '-',
                'doses' => $row->doses,
                'date' => optional($row->vaccination_date)->format('d/m/Y'),
                'notes' => $row->notes,
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Vaccination records fetched successfully',
            'data' => $rows,
        ]);
    }

    public function storeVaccination(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'animal_id' => 'required|exists:animals,id',
            'vaccine_id' => 'required|exists:vaccines,id',
            'doses' => 'required|string|max:255',
            'vaccination_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $animal = Animal::with('pan')
            ->where('id', $data['animal_id'])
            ->where('farmer_id', $data['farmer_id'])
            ->first();

        if (! $animal) {
            return response()->json([
                'status' => false,
                'message' => ['animal_id' => ['Selected animal is invalid.']],
            ], 422);
        }

        $vaccine = Vaccine::query()
            ->where('id', $data['vaccine_id'])
            ->where('is_active', true)
            ->first();

        if (! $vaccine) {
            return response()->json([
                'status' => false,
                'message' => ['vaccine_id' => ['Selected vaccine is invalid.']],
            ], 422);
        }

        $row = AnimalVaccination::create([
            'farmer_id' => (int) $data['farmer_id'],
            'animal_id' => (int) $animal->id,
            'pan_id' => $animal->pan_id,
            'pan_name' => optional($animal->pan)->name,
            'vaccine_id' => (int) $vaccine->id,
            'doses' => trim((string) $data['doses']),
            'vaccination_date' => $data['vaccination_date'] ?? now()->toDateString(),
            'notes' => trim((string) ($data['notes'] ?? '')),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Vaccination record saved successfully',
            'data' => $row,
        ], 201);
    }

    public function mastitisList($farmerId)
{
    $rows = MastitisRecord::with(['animal.animalType'])
        ->where('farmer_id', $farmerId)
        ->latest('date')
        ->latest('id')
        ->get()
        ->map(fn ($row) => [
            'id' => $row->id,
            'case_id' => $row->case_id ?: $row->id,
            'animal_id' => $row->animal_id,
            'animal_name' => $row->animal->animal_name ?? '-',
            'tag_number' => $row->animal->tag_number ?? '-',
            'animal_type_name' => optional(optional($row->animal)->animalType)->name ?? '',
            'test_result' => $row->test_result,
            'treatment' => $row->treatment,
            'recovery_status' => $row->recovery_status,
            'quarter' => $row->quarter,
            'clinical_type' => $row->clinical_type,
            'cmt_score' => $row->cmt_score,
            'scc_count' => $row->scc_count !== null ? (float) $row->scc_count : null,
            'date' => optional($row->date)->format('d/m/Y'),
            'follow_up_date' => optional($row->follow_up_date)->format('d/m/Y'),
            'notes' => $row->notes,
        ]);

    return response()->json([
        'status' => true,
        'message' => 'Mastitis records fetched successfully',
        'data' => $rows
    ]);
}    public function storeMastitis(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'animal_id' => 'required|exists:animals,id',
            'test_result' => 'required|in:positive,negative',
            'treatment' => 'nullable|string|max:255',
            'recovery_status' => 'nullable|string|max:255',
            'quarter' => 'nullable|string|max:50',
            'clinical_type' => 'nullable|string|max:50',
            'cmt_score' => 'nullable|string|max:20',
            'scc_count' => 'nullable|numeric|min:0',
            'date' => 'nullable|date',
            'follow_up_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $animal = Animal::with('animalType')
            ->where('id', $data['animal_id'])
            ->where('farmer_id', $data['farmer_id'])
            ->first();

        if (! $animal) {
            return response()->json(['status' => false, 'message' => 'Selected animal is invalid.'], 422);
        }

        if (! $this->isMilkingCow($animal)) {
            return response()->json(['status' => false, 'message' => ['animal_id' => ['Only milking cows can be selected for mastitis record.']]], 422);
        }

        $hasActiveCase = MastitisRecord::query()
            ->where('farmer_id', $data['farmer_id'])
            ->where('animal_id', $data['animal_id'])
            ->whereNull('case_id')
            ->whereNotIn('recovery_status', ['recovered', 'Recovered', 'recoverd', 'Recoverd'])
            ->exists();

        if ($hasActiveCase) {
            return response()->json([
                'status' => false,
                'message' => ['animal_id' => ['This animal is already under treatment for mastitis. Mark it recovered before adding a new mastitis record.']],
            ], 422);
        }

        $data['date'] = $data['date'] ?? now()->toDateString();
        $data['treatment'] = trim((string) ($data['treatment'] ?? ''));
        $data['recovery_status'] = $data['test_result'] === 'positive' ? 'under_treatment' : 'recovered';

        $row = MastitisRecord::create($data);
        return response()->json(['status' => true, 'message' => 'Mastitis record saved successfully', 'data' => $row], 201);
    }

    public function updateMastitis(Request $request, MastitisRecord $record)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'animal_id' => 'required|exists:animals,id',
            'test_result' => 'required|in:positive,negative',
            'treatment' => 'nullable|string|max:255',
            'recovery_status' => 'nullable|string|max:255',
            'quarter' => 'nullable|string|max:50',
            'clinical_type' => 'nullable|string|max:50',
            'cmt_score' => 'nullable|string|max:20',
            'scc_count' => 'nullable|numeric|min:0',
            'date' => 'required|date',
            'follow_up_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }

        if ((int) $record->farmer_id !== (int) $request->farmer_id) {
            return response()->json(['status' => false, 'message' => 'Mastitis record not found for this farmer.'], 404);
        }

        $data = $validator->validated();
        $data['treatment'] = trim((string) ($data['treatment'] ?? ''));
        $data['recovery_status'] = $data['test_result'] === 'positive'
            ? ($data['recovery_status'] ?? 'under_treatment')
            : 'recovered';

        $record->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Mastitis record updated successfully',
            'data' => $record->fresh(),
        ]);
    }

    public function storeMastitisTreatment(Request $request)
{
    $validator = Validator::make($request->all(), [
        'farmer_id' => 'required|exists:farmers,id',
        'animal_id' => 'required|exists:animals,id',
        'mastitis_record_id' => 'nullable|exists:mastitis_records,id',
        'treatment' => 'required|string|max:255',
        'date' => 'nullable|date',
        'notes' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => false, 'message' => $validator->errors()], 422);
    }

    $data = $validator->validated();

    $animal = Animal::with('animalType')
        ->where('id', $data['animal_id'])
        ->where('farmer_id', $data['farmer_id'])
        ->first();

    if (! $animal || ! $this->isMilkingCow($animal)) {
        return response()->json(['status' => false, 'message' => 'Selected milking cow is invalid.'], 422);
    }

    if (! empty($data['mastitis_record_id'])) {
        $case = MastitisRecord::where('id', $data['mastitis_record_id'])
            ->where('farmer_id', $data['farmer_id'])
            ->where('animal_id', $data['animal_id'])
            ->whereNull('case_id')
            ->first();
    } else {
        $case = MastitisRecord::where('farmer_id', $data['farmer_id'])
            ->where('animal_id', $data['animal_id'])
            ->whereNull('case_id')
            ->whereNotIn('recovery_status', ['recovered', 'Recovered', 'recoverd', 'Recoverd'])
            ->latest('date')
            ->latest('id')
            ->first();
    }

    if (! $case) {
        return response()->json([
            'status' => false,
            'message' => 'Active mastitis record not found. Please create new mastitis record first.'
        ], 422);
    }

    $caseStatus = strtolower(str_replace(' ', '_', (string) $case->recovery_status));

    if (in_array($caseStatus, ['recovered', 'recoverd'], true)) {
        return response()->json([
            'status' => false,
            'message' => 'This mastitis record is already recovered. Please create a new mastitis record.'
        ], 422);
    }

    $row = MastitisRecord::create([
        'case_id' => $case->id,
        'farmer_id' => $data['farmer_id'],
        'animal_id' => $data['animal_id'],
        'test_result' => 'positive',
        'treatment' => trim((string) $data['treatment']),
        'recovery_status' => 'under_treatment',
        'date' => $data['date'] ?? now()->toDateString(),
        'notes' => $data['notes'] ?? null,
    ]);

    $case->update([
        'recovery_status' => 'under_treatment',
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Treatment added successfully',
        'data' => $row
    ], 201);
}

public function recoverMastitis(Request $request)
{
    $validator = Validator::make($request->all(), [
        'farmer_id' => 'required|exists:farmers,id',
        'animal_id' => 'required|exists:animals,id',
        'mastitis_record_id' => 'nullable|exists:mastitis_records,id',
        'date' => 'nullable|date',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validator->errors()
        ], 422);
    }

    $data = $validator->validated();

    $animal = Animal::where('id', $data['animal_id'])
        ->where('farmer_id', $data['farmer_id'])
        ->first();

    if (! $animal) {
        return response()->json([
            'status' => false,
            'message' => 'Selected animal is invalid.'
        ], 422);
    }

    if (! empty($data['mastitis_record_id'])) {
        $case = MastitisRecord::where('id', $data['mastitis_record_id'])
            ->where('farmer_id', $data['farmer_id'])
            ->where('animal_id', $data['animal_id'])
            ->first();
    } else {
        $case = MastitisRecord::where('farmer_id', $data['farmer_id'])
            ->where('animal_id', $data['animal_id'])
            ->whereNotIn('recovery_status', ['recovered', 'Recovered', 'recoverd', 'Recoverd'])
            ->latest('date')
            ->latest('id')
            ->first();
    }

    if (! $case) {
        return response()->json([
            'status' => false,
            'message' => 'Active mastitis record not found.'
        ], 422);
    }

    // Important: update main mastitis case also
    $case->update([
        'test_result' => 'negative',
        'recovery_status' => 'recovered',
        'date' => $data['date'] ?? $case->date ?? now()->toDateString(),
    ]);

    // Keep recovered entry also for history/date-wise display
    $rowData = [
        'farmer_id' => $data['farmer_id'],
        'animal_id' => $data['animal_id'],
        'test_result' => 'negative',
        'treatment' => 'Recovered',
        'recovery_status' => 'recovered',
        'date' => $data['date'] ?? now()->toDateString(),
    ];

    if (\Illuminate\Support\Facades\Schema::hasColumn('mastitis_records', 'case_id')) {
        $rowData['case_id'] = $case->id;
    }

    $row = MastitisRecord::create($rowData);

    return response()->json([
        'status' => true,
        'message' => 'Animal marked as recovered',
        'data' => $row
    ], 201);
}
public function dmiList(Request $request, $farmerId)
    {
        try {
            $fromDate = $request->filled('from_date')
                ? Carbon::parse($request->query('from_date'))->startOfDay()
                : now()->startOfDay();
            $toDate = $request->filled('to_date')
                ? Carbon::parse($request->query('to_date'))->startOfDay()
                : $fromDate->copy();
        } catch (\Throwable $e) {
            $fromDate = now()->startOfDay();
            $toDate = now()->startOfDay();
        }

        if ($fromDate->greaterThan($toDate)) {
            $toDate = $fromDate->copy();
        }

        $rows = Animal::query()
            ->with(['animalType', 'farmer', 'pan'])
            ->where('farmer_id', $farmerId)
            ->where('is_active', true)
            ->latest('id')
            ->get()
            ->flatMap(function ($animal) use ($fromDate, $toDate) {
                $animalRows = [];

                for ($date = $fromDate->copy(); $date->lte($toDate); $date->addDay()) {
                    $bodyWeight = (float) ($animal->weight ?? 0);
                    $typeName = mb_strtolower(trim((string) ($animal->animalType->name ?? '')));
                    $isNonMilkingType = $this->isNonMilkingAnimalTypeName($typeName);
                    $totalMilk = $this->resolveDmiDailyMilk($animal, $date->toDateString(), $isNonMilkingType);
                    $actualDmi = round(
                        FeedingRecord::query()
                            ->with('dietPlan')
                            ->where('animal_id', $animal->id)
                            ->whereDate('date', $date->toDateString())
                            ->get()
                            ->sum(fn (FeedingRecord $record) => $this->calculateRecordActualDryMatter($record)),
                        2
                    );
                    $isMilking = ! $isNonMilkingType
                        && (str_contains($typeName, 'milking') || $totalMilk > 0);

                    $requiredDmi = $isNonMilkingType
                        ? round(($bodyWeight * 0.025), 2)
                        : ($totalMilk > 0
                            ? round(($bodyWeight * 0.02) + ($totalMilk * 0.33), 2)
                            : 0.0);
                    $dmiDifference = round($actualDmi - $requiredDmi, 2);
                    $alertStatus = abs($dmiDifference) <= 0.5
                        ? 'Balanced'
                        : ($dmiDifference < 0 ? 'Low' : 'High');

                    $animalRows[] = [
                        'id' => (int) $animal->id,
                        'animal_id' => (int) $animal->id,
                        'animal_name' => $animal->animal_name ?? '-',
                        'tag_number' => $animal->tag_number ?? '-',
                        'animal_type_name' => $animal->animalType->name ?? '-',
                        'dmi_type' => $isMilking ? 'Milking Cow' : 'Non Milking Cow',
                        'body_weight' => round($bodyWeight, 2),
                        'total_milk' => round($totalMilk, 2),
                        'required_dmi' => $requiredDmi,
                        'actual_dmi' => $actualDmi,
                        'alert_status' => $alertStatus,
                        'date' => $date->format('d/m/Y'),
                        'notes' => '',
                        'pan_id' => (int) ($animal->pan_id ?? 0),
                        'pan_name' => $animal->pan->name ?? '',
                    ];
                }

                return $animalRows;
            })
            ->values();

        return response()->json(['status' => true, 'message' => 'DMI records fetched successfully', 'data' => $rows]);
    }

    public function storeDmi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'animal_id' => 'required|exists:animals,id',
            'body_weight' => 'required|numeric|min:0',
            'total_milk' => 'required|numeric|min:0',
            'actual_dmi' => 'required|numeric|min:0',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $isMilking = (float) $data['total_milk'] > 0;
        $requiredDmi = $isMilking
            ? round(($data['body_weight'] * 0.02) + ($data['total_milk'] * 0.33), 2)
            : 0.0;
        $difference = round($data['actual_dmi'] - $requiredDmi, 2);
        $status = abs($difference) <= 0.5 ? 'Balanced' : ($difference < 0 ? 'Low' : 'High');

        $row = DmiRecord::create($data + [
            'required_dmi' => $requiredDmi,
            'alert_status' => $status,
        ]);

        return response()->json(['status' => true, 'message' => 'DMI record saved successfully', 'data' => $row], 201);
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
            $nextQty = $existingQty + $qty;
            $bucket[$key]['dm_percent'] = $nextQty > 0
                ? round((($existingQty * $existingDm) + ($qty * $dmPercent)) / $nextQty, 2)
                : $dmPercent;
            $bucket[$key]['quantity'] = round($nextQty, 2);
        }

        return array_values($bucket);
    }

    private function isMilkingCow(Animal $animal): bool
    {
        $typeName = mb_strtolower(trim((string) optional($animal->animalType)->name));
        if ($typeName === '') {
            return false;
        }

        return str_contains($typeName, 'milking')
            && ! str_contains($typeName, 'non')
            && ! str_contains($typeName, 'dry')
            && ! str_contains($typeName, 'calf')
            && ! str_contains($typeName, 'heifer');
    }

    private function isNonMilkingAnimalTypeName(string $typeName): bool
    {
        return str_contains($typeName, 'non')
            || str_contains($typeName, 'dry')
            || str_contains($typeName, 'heifer')
            || str_contains($typeName, 'calf')
            || str_contains($typeName, 'bull');
    }

    private function resolveDmiDailyMilk(Animal $animal, string $date, bool $isNonMilkingType): float
    {
        if ($isNonMilkingType) {
            return 0.0;
        }

        return round((float) MilkProduction::query()
            ->where('animal_id', $animal->id)
            ->whereDate('date', $date)
            ->sum('total_milk'), 2);
    }
}
