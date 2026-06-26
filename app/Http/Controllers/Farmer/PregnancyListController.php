<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Animal;
use App\Models\Farmer\AnimalPregnancy;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PregnancyListController extends Controller
{
    public function index()
    {
        $records = AnimalPregnancy::with(['farmer', 'animal.animalType', 'calfAnimal'])
            ->latest('is_current')
            ->latest('pregnancy_no')
            ->latest('service_no')
            ->latest('ai_date')
            ->get();

        $summary = [
            'total' => $records->count(),
            'current' => $records->where('is_current', true)->count(),
            'pregnant' => $records->where('status', 'pregnant')->count(),
            'calved' => $records->where('status', 'calved')->count(),
        ];

        return view('pregnancy.index', compact('records', 'summary'));
    }

    public function create()
    {
        $animals = Animal::query()
            ->with(['farmer', 'animalType'])
            ->orderBy('animal_name')
            ->get();

        $animalDefaults = $animals->mapWithKeys(function (Animal $animal) {
            $next = $this->nextNumbers($animal->id);

            return [
                $animal->id => [
                    'pregnancy_no' => $next['pregnancy_no'],
                    'service_no' => $next['service_no'],
                    'lactation_number' => (int) ($animal->lactation_number ?? 0),
                ],
            ];
        })->all();

        return view('pregnancy.create', compact('animals', 'animalDefaults'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'animal_id' => ['required', 'exists:animals,id'],
            'lactation_number' => ['nullable', 'integer', 'min:0'],
            'heat_date' => ['nullable', 'date'],
            'ai_date' => ['required', 'date'],
            'service_type' => ['required', 'in:ai,natural'],
            'bull_name' => ['nullable', 'string', 'max:120'],
            'semen_no' => ['nullable', 'string', 'max:120'],
            'doctor_name' => ['nullable', 'string', 'max:120'],
            'pregnancy_check_due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $animal = Animal::query()->find((int) $data['animal_id']);
        if (! $animal) {
            return back()->withErrors([
                'animal_id' => 'Animal not found.',
            ])->withInput();
        }

        $this->syncAnimalLactationNumber($animal, $request);

        $aiDate = Carbon::parse($data['ai_date']);
        $status = 'served';
        $numbers = $this->nextNumbers($animal->id);

        $payload = [
            'farmer_id' => $animal->farmer_id,
            'animal_id' => $animal->id,
            'pregnancy_no' => (int) $numbers['pregnancy_no'],
            'service_no' => (int) $numbers['service_no'],
            'heat_date' => $data['heat_date'] ?? null,
            'ai_date' => $aiDate->toDateString(),
            'service_type' => $data['service_type'],
            'bull_name' => $data['bull_name'] ?? null,
            'semen_no' => $data['semen_no'] ?? null,
            'doctor_name' => $data['doctor_name'] ?? null,
            'pregnancy_check_due_date' => $data['pregnancy_check_due_date'],
            'pregnancy_check_date' => null,
            'pregnancy_result' => 'pending',
            'expected_calving_date' => $aiDate->copy()->addDays(283)->toDateString(),
            'dry_off_date' => $aiDate->copy()->addDays(223)->toDateString(),
            'calving_date' => null,
            'status' => $status,
            'calf_animal_id' => null,
            'notes' => $data['notes'] ?? null,
            'is_current' => true,
        ];

        $this->closeOtherCurrentRecords($animal->id);
        AnimalPregnancy::create($payload);

        return redirect()
            ->route('farmer.pregnancy')
            ->with('success', 'Pregnancy record saved successfully.');
    }

    private function nextNumbers(int $animalId): array
    {
        $latest = AnimalPregnancy::where('animal_id', $animalId)
            ->latest('pregnancy_no')
            ->latest('service_no')
            ->latest('id')
            ->first();

        if (! $latest) {
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

    private function closeOtherCurrentRecords(int $animalId, ?int $exceptId = null): void
    {
        AnimalPregnancy::where('animal_id', $animalId)
            ->where('is_current', true)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->update(['is_current' => false]);
    }

    private function syncAnimalLactationNumber(Animal $animal, Request $request): void
    {
        if ($request->filled('lactation_number')) {
            $animal->update([
                'lactation_number' => (int) $request->input('lactation_number'),
            ]);
        }
    }
}
