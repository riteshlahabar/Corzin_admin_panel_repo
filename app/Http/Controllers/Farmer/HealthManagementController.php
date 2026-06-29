<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Animal;
use App\Models\Farmer\AnimalVaccination;
use App\Models\Farmer\DmiRecord;
use App\Models\Farmer\Farmer;
use App\Models\Farmer\MastitisRecord;
use App\Models\Farmer\MedicalRecord;
use App\Models\Farmer\MilkProduction;
use App\Models\Farmer\Vaccine;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HealthManagementController extends Controller
{
    public function medical()
    {
        $rows = MedicalRecord::with(['farmer', 'animal'])->latest('date')->get();
        return view('health.medical', [
            'title' => 'Medical Records',
            'rows' => $rows,
            'farmers' => Farmer::orderBy('first_name')->get(),
            'animals' => Animal::with('farmer')->orderBy('animal_name')->get(),
        ]);
    }

    public function storeMedical(Request $request)
    {
        $data = $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'animal_id' => 'required|exists:animals,id',
            'medicine_name' => 'required|string|max:255',
            'dose' => 'required|string|max:255',
            'date' => 'required|date',
            'disease' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        MedicalRecord::create($data);

        return redirect()->route('health.medical')->with('success', 'Medical record added successfully.');
    }

    public function mastitis()
    {
        $rows = MastitisRecord::with(['farmer', 'animal'])->latest('date')->get();
        return view('health.mastitis', [
            'title' => 'Mastitis Monitoring',
            'rows' => $rows,
            'farmers' => Farmer::orderBy('first_name')->get(),
            'animals' => Animal::with('farmer')->orderBy('animal_name')->get(),
        ]);
    }

    public function storeMastitis(Request $request)
{
    $data = $request->validate([
        'animal_id' => 'required|exists:animals,id',
        'test_result' => 'required|string|max:255',

        // Not required now
        'farmer_id' => 'nullable|exists:farmers,id',
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

    $animal = Animal::findOrFail($data['animal_id']);

    // If farmer_id not coming from form, take farmer from selected animal
    $data['farmer_id'] = $data['farmer_id'] ?? $animal->farmer_id;

    // If date not coming from form, use today date
    $data['date'] = $data['date'] ?? now()->toDateString();

    // Auto recovery status
    $testResult = strtolower((string) $data['test_result']);

    $data['recovery_status'] = $data['recovery_status'] ?? (
        $testResult === 'positive' ? 'under_treatment' : 'recovered'
    );

    // Treatment optional
    $data['treatment'] = $data['treatment'] ?? '';

    MastitisRecord::create($data);

        return redirect()->route('health.mastitis')->with('success', 'Mastitis record added successfully.');
}

    public function storeMastitisTreatment(Request $request)
    {
        $data = $request->validate([
            'mastitis_record_id' => 'required|exists:mastitis_records,id',
            'treatment' => 'required|string|max:255',
            'date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $selectedRecord = MastitisRecord::with('animal')->findOrFail((int) $data['mastitis_record_id']);
        $caseId = (int) ($selectedRecord->case_id ?: $selectedRecord->id);

        $case = MastitisRecord::query()
            ->where('id', $caseId)
            ->where('farmer_id', (int) $selectedRecord->farmer_id)
            ->where('animal_id', (int) $selectedRecord->animal_id)
            ->first();

        if (! $case) {
            return back()->withErrors([
                'treatment' => 'Active mastitis case not found.',
            ])->withInput();
        }

        $caseStatus = strtolower(str_replace(' ', '_', (string) $case->recovery_status));
        if (in_array($caseStatus, ['recovered', 'recoverd'], true)) {
            return back()->withErrors([
                'treatment' => 'This mastitis case is already recovered.',
            ])->withInput();
        }

        MastitisRecord::create([
            'case_id' => $case->id,
            'farmer_id' => $case->farmer_id,
            'animal_id' => $case->animal_id,
            'test_result' => 'positive',
            'treatment' => trim((string) $data['treatment']),
            'recovery_status' => 'under_treatment',
            'date' => $data['date'] ?? now()->toDateString(),
            'notes' => trim((string) ($data['notes'] ?? '')),
        ]);

        $case->update([
            'recovery_status' => 'under_treatment',
        ]);

        return redirect()->route('health.mastitis')->with('success', 'Treatment added successfully.');
    }

    public function recoverMastitis(Request $request)
    {
        $data = $request->validate([
            'mastitis_record_id' => 'required|exists:mastitis_records,id',
            'date' => 'nullable|date',
        ]);

        $selectedRecord = MastitisRecord::findOrFail((int) $data['mastitis_record_id']);
        $caseId = (int) ($selectedRecord->case_id ?: $selectedRecord->id);

        $case = MastitisRecord::query()
            ->where('id', $caseId)
            ->where('farmer_id', (int) $selectedRecord->farmer_id)
            ->where('animal_id', (int) $selectedRecord->animal_id)
            ->first();

        if (! $case) {
            return back()->withErrors([
                'mastitis_record_id' => 'Active mastitis case not found.',
            ]);
        }

        $case->update([
            'test_result' => 'negative',
            'recovery_status' => 'recovered',
            'date' => $data['date'] ?? $case->date ?? now()->toDateString(),
        ]);

        MastitisRecord::create([
            'case_id' => $case->id,
            'farmer_id' => $case->farmer_id,
            'animal_id' => $case->animal_id,
            'test_result' => 'negative',
            'treatment' => 'Recovered',
            'recovery_status' => 'recovered',
            'date' => $data['date'] ?? now()->toDateString(),
        ]);

        return redirect()->route('health.mastitis')->with('success', 'Animal marked as recovered successfully.');
    }

    public function vaccination()
    {
        $rows = AnimalVaccination::with(['farmer', 'animal', 'vaccine'])
            ->latest('vaccination_date')
            ->latest('id')
            ->get();

        return view('health.vaccination', [
            'title' => 'Vaccination',
            'rows' => $rows,
            'farmers' => Farmer::orderBy('first_name')->orderBy('last_name')->get(),
            'animals' => Animal::with(['farmer', 'pan'])->orderBy('animal_name')->get(),
            'vaccines' => Vaccine::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeVaccination(Request $request)
    {
        $data = $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'animal_id' => 'required|exists:animals,id',
            'vaccine_id' => 'required|exists:vaccines,id',
            'doses' => 'required|string|max:255',
            'vaccination_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $animal = Animal::with('pan')
            ->where('id', (int) $data['animal_id'])
            ->where('farmer_id', (int) $data['farmer_id'])
            ->first();

        if (! $animal) {
            return back()->withErrors([
                'animal_id' => 'Selected animal is not valid for this farmer.',
            ])->withInput();
        }

        AnimalVaccination::create([
            'farmer_id' => $animal->farmer_id,
            'animal_id' => $animal->id,
            'pan_id' => $animal->pan_id,
            'pan_name' => optional($animal->pan)->name,
            'vaccine_id' => (int) $data['vaccine_id'],
            'doses' => trim((string) $data['doses']),
            'vaccination_date' => $data['vaccination_date'] ?? now()->toDateString(),
            'notes' => trim((string) ($data['notes'] ?? '')),
        ]);

        return redirect()->route('health.vaccination')->with('success', 'Vaccination record added successfully.');
    }

    public function dmi(Request $request)
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
            ->with(['farmer', 'animalType'])
            ->where('is_active', true)
            ->latest('id')
            ->get()
            ->flatMap(function (Animal $animal) use ($fromDate, $toDate) {
                $animalRows = [];

                for ($date = $fromDate->copy(); $date->lte($toDate); $date->addDay()) {
                    $bodyWeight = (float) ($animal->weight ?? 0);
                    $totalMilk = (float) MilkProduction::query()
                        ->where('animal_id', $animal->id)
                        ->whereDate('date', $date->toDateString())
                        ->sum('total_milk');

                    $typeName = mb_strtolower(trim((string) ($animal->animalType->name ?? '')));
                    $isNonMilkingType = str_contains($typeName, 'non')
                        || str_contains($typeName, 'dry')
                        || str_contains($typeName, 'heifer')
                        || str_contains($typeName, 'calf');
                    $isMilking = ! $isNonMilkingType
                        && (str_contains($typeName, 'milking') || $totalMilk > 0);

                    $requiredDmi = $isMilking
                        ? round(($bodyWeight * 0.02) + ($totalMilk * 0.33), 2)
                        : round(($bodyWeight * 0.025), 2);

                    $animalRows[] = (object) [
                        'farmer' => $animal->farmer,
                        'animal' => $animal,
                        'dmi_type' => $isMilking ? 'Milking Cow' : 'Non Milking Cow',
                        'body_weight' => round($bodyWeight, 2),
                        'total_milk' => round($totalMilk, 2),
                        'required_dmi' => $requiredDmi,
                        'actual_dmi' => $requiredDmi,
                        'alert_status' => 'Auto Calculated',
                        'date' => $date->copy(),
                    ];
                }

                return $animalRows;
            })
            ->values();

        return view('health.dmi', [
            'title' => 'DMI Calculator',
            'rows' => $rows,
        ]);
    }

    public function storeDmi(Request $request)
    {
        $data = $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'animal_id' => 'required|exists:animals,id',
            'body_weight' => 'required|numeric|min:0',
            'total_milk' => 'required|numeric|min:0',
            'actual_dmi' => 'required|numeric|min:0',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $isMilking = (float) $data['total_milk'] > 0;
        $requiredDmi = $isMilking
            ? round(($data['body_weight'] * 0.02) + ($data['total_milk'] * 0.33), 2)
            : round($data['body_weight'] * 0.025, 2);
        $difference = round($data['actual_dmi'] - $requiredDmi, 2);
        $status = abs($difference) <= 0.5 ? 'Balanced' : ($difference < 0 ? 'Low' : 'High');

        DmiRecord::create($data + [
            'required_dmi' => $requiredDmi,
            'alert_status' => $status,
        ]);

        return redirect()->route('health.dmi')->with('success', 'DMI record added successfully.');
    }
}
