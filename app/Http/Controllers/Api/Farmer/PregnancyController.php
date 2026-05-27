<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Animal;
use App\Models\Farmer\AnimalPregnancy;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PregnancyController extends Controller
{
    private const STATUSES = [
        'served',
        'pregnancy_check_due',
        'pregnant',
        'not_pregnant',
        'repeat_heat',
        'aborted',
        'calved',
    ];

    public function index($farmerId)
    {
        $records = AnimalPregnancy::with(['animal.animalType', 'calfAnimal'])
            ->where('farmer_id', $farmerId)
            ->latest('is_current')
            ->latest('pregnancy_no')
            ->latest('service_no')
            ->latest('ai_date')
            ->latest('id')
            ->get()
            ->map(fn (AnimalPregnancy $record) => $this->transform($record));

        return response()->json([
            'status' => true,
            'message' => 'Pregnancy records fetched successfully.',
            'data' => $records,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $animal = Animal::find($request->animal_id);
        if (! $animal) {
            return response()->json([
                'status' => false,
                'message' => 'Animal not found.',
            ], 404);
        }

        $this->syncAnimalLactationNumber($animal, $request);

        $payload = $this->payload($request, $animal);
        $this->closeOtherCurrentRecords($animal->id);

        $record = AnimalPregnancy::create($payload);

        return response()->json([
            'status' => true,
            'message' => 'Pregnancy record saved successfully.',
            'data' => $this->transform($record->load(['animal.animalType', 'calfAnimal'])),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $record = AnimalPregnancy::findOrFail($id);
        $validator = Validator::make($request->all(), $this->rules($record->id));
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $animal = Animal::find($request->animal_id);
        if (! $animal) {
            return response()->json([
                'status' => false,
                'message' => 'Animal not found.',
            ], 404);
        }

        $this->syncAnimalLactationNumber($animal, $request);

        $payload = $this->payload($request, $animal);
        if ((bool) $payload['is_current']) {
            $this->closeOtherCurrentRecords($animal->id, $record->id);
        }

        $record->update($payload);

        return response()->json([
            'status' => true,
            'message' => 'Pregnancy record updated successfully.',
            'data' => $this->transform($record->fresh()->load(['animal.animalType', 'calfAnimal'])),
        ]);
    }

    public function status(Request $request, $id)
    {
        $record = AnimalPregnancy::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(self::STATUSES)],
            'pregnancy_result' => 'nullable|in:pending,pregnant,not_pregnant',
            'pregnancy_check_date' => 'nullable|date',
            'calving_date' => 'nullable|date',
            'calf_animal_id' => 'nullable|exists:animals,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $status = $request->status;
        $payload = [
            'status' => $status,
            'is_current' => $this->isCurrentStatus($status),
            'pregnancy_result' => $request->pregnancy_result ?: $this->resultForStatus($status, $record->pregnancy_result),
        ];

        foreach (['pregnancy_check_date', 'calving_date', 'calf_animal_id', 'notes'] as $field) {
            if ($request->has($field)) {
                $payload[$field] = $request->input($field);
            }
        }

        if ($status === 'calved' && empty($payload['calving_date'])) {
            $payload['calving_date'] = now()->toDateString();
        }

        if ((bool) $payload['is_current']) {
            $this->closeOtherCurrentRecords($record->animal_id, $record->id);
        }

        $record->update($payload);

        return response()->json([
            'status' => true,
            'message' => 'Pregnancy status updated successfully.',
            'data' => $this->transform($record->fresh()->load(['animal.animalType', 'calfAnimal'])),
        ]);
    }

    public function destroy($id)
    {
        $record = AnimalPregnancy::findOrFail($id);
        $record->delete();

        return response()->json([
            'status' => true,
            'message' => 'Pregnancy record deleted successfully.',
        ]);
    }

    private function rules(?int $recordId = null): array
    {
        return [
            'animal_id' => 'required|exists:animals,id',
            'pregnancy_no' => 'nullable|integer|min:1',
            'service_no' => 'nullable|integer|min:1',
            'heat_date' => 'nullable|date',
            'ai_date' => 'required|date',
            'service_type' => 'required|in:ai,natural',
            'bull_name' => 'nullable|string|max:120',
            'semen_no' => 'nullable|string|max:120',
            'doctor_name' => 'nullable|string|max:120',
            'lactation_number' => 'nullable|integer|min:0',
            'pregnancy_check_due_date' => 'nullable|date',
            'pregnancy_check_date' => 'nullable|date',
            'pregnancy_result' => 'nullable|in:pending,pregnant,not_pregnant',
            'expected_calving_date' => 'nullable|date',
            'dry_off_date' => 'nullable|date',
            'calving_date' => 'nullable|date',
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'calf_animal_id' => 'nullable|exists:animals,id',
            'notes' => 'nullable|string',
            'is_current' => 'nullable|boolean',
        ];
    }

    private function payload(Request $request, Animal $animal): array
    {
        $aiDate = Carbon::parse($request->ai_date);
        $status = $request->input('status', 'served');
        $numbers = $this->nextNumbers($animal->id);

        return [
            'farmer_id' => $animal->farmer_id,
            'animal_id' => $animal->id,
            'pregnancy_no' => (int) $request->input('pregnancy_no', $numbers['pregnancy_no']),
            'service_no' => (int) $request->input('service_no', $numbers['service_no']),
            'heat_date' => $request->heat_date,
            'ai_date' => $aiDate->toDateString(),
            'service_type' => $request->service_type,
            'bull_name' => $request->bull_name,
            'semen_no' => $request->semen_no,
            'doctor_name' => $request->doctor_name,
            'pregnancy_check_due_date' => $request->pregnancy_check_due_date ?: $aiDate->copy()->addDays(30)->toDateString(),
            'pregnancy_check_date' => $request->pregnancy_check_date,
            'pregnancy_result' => $request->input('pregnancy_result', $this->resultForStatus($status, 'pending')),
            'expected_calving_date' => $request->expected_calving_date ?: $aiDate->copy()->addDays(283)->toDateString(),
            'dry_off_date' => $request->dry_off_date ?: $aiDate->copy()->addDays(223)->toDateString(),
            'calving_date' => $request->calving_date,
            'status' => $status,
            'calf_animal_id' => $request->calf_animal_id,
            'notes' => $request->notes,
            'is_current' => $request->has('is_current') ? (bool) $request->boolean('is_current') : $this->isCurrentStatus($status),
        ];
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

    private function isCurrentStatus(string $status): bool
    {
        return in_array($status, ['served', 'pregnancy_check_due', 'pregnant'], true);
    }

    private function resultForStatus(string $status, ?string $fallback): string
    {
        return match ($status) {
            'pregnant', 'calved' => 'pregnant',
            'not_pregnant', 'repeat_heat' => 'not_pregnant',
            default => $fallback ?: 'pending',
        };
    }

    private function syncAnimalLactationNumber(Animal $animal, Request $request): void
    {
        if (! $request->filled('lactation_number')) {
            return;
        }

        $lactationNumber = (int) $request->input('lactation_number');
        if ($lactationNumber < 0) {
            return;
        }

        if ((int) $animal->lactation_number !== $lactationNumber) {
            $animal->lactation_number = $lactationNumber;
            $animal->save();
        }
    }

    private function transform(AnimalPregnancy $record): array
    {
        $expected = $record->expected_calving_date ? Carbon::parse($record->expected_calving_date)->startOfDay() : null;
        $remainingDays = $expected ? now()->startOfDay()->diffInDays($expected, false) : null;

        return [
            'id' => $record->id,
            'farmer_id' => $record->farmer_id,
            'animal_id' => $record->animal_id,
            'animal_name' => optional($record->animal)->animal_name,
            'unique_id' => optional($record->animal)->unique_id,
            'tag_number' => optional($record->animal)->tag_number,
            'image' => optional($record->animal)->image_url,
            'animal_type_name' => optional(optional($record->animal)->animalType)->name,
            'pregnancy_no' => (int) $record->pregnancy_no,
            'service_no' => (int) $record->service_no,
            'heat_date' => optional($record->heat_date)->format('Y-m-d'),
            'ai_date' => optional($record->ai_date)->format('Y-m-d'),
            'service_type' => $record->service_type,
            'bull_name' => $record->bull_name,
            'semen_no' => $record->semen_no,
            'doctor_name' => $record->doctor_name,
            'pregnancy_check_due_date' => optional($record->pregnancy_check_due_date)->format('Y-m-d'),
            'pregnancy_check_date' => optional($record->pregnancy_check_date)->format('Y-m-d'),
            'pregnancy_result' => $record->pregnancy_result,
            'expected_calving_date' => optional($record->expected_calving_date)->format('Y-m-d'),
            'dry_off_date' => optional($record->dry_off_date)->format('Y-m-d'),
            'calving_date' => optional($record->calving_date)->format('Y-m-d'),
            'status' => $record->status,
            'calf_animal_id' => $record->calf_animal_id,
            'calf_animal_name' => optional($record->calfAnimal)->animal_name,
            'notes' => $record->notes,
            'is_current' => (bool) $record->is_current,
            'remaining_days' => $remainingDays,
        ];
    }
}
