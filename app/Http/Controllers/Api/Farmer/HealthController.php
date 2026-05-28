<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Animal;
use App\Models\Farmer\DmiRecord;
use App\Models\Farmer\MastitisRecord;
use App\Models\Farmer\MedicalRecord;
use App\Models\Farmer\MilkProduction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HealthController extends Controller
{
    public function medicalList($farmerId)
    {
        $rows = MedicalRecord::with('animal')
            ->where('farmer_id', $farmerId)
            ->latest('date')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
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

    public function mastitisList($farmerId)
    {
        $rows = MastitisRecord::with('animal')
            ->where('farmer_id', $farmerId)
            ->latest('date')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'animal_id' => $row->animal_id,
                'animal_name' => $row->animal->animal_name ?? '-',
                'tag_number' => $row->animal->tag_number ?? '-',
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

        return response()->json(['status' => true, 'message' => 'Mastitis records fetched successfully', 'data' => $rows]);
    }

    public function storeMastitis(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'animal_id' => 'required|exists:animals,id',
            'test_result' => 'required|string|max:255',
            'treatment' => 'required|string|max:255',
            'recovery_status' => 'required|string|max:255',
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

        $row = MastitisRecord::create($validator->validated());
        return response()->json(['status' => true, 'message' => 'Mastitis record saved successfully', 'data' => $row], 201);
    }

    public function updateMastitis(Request $request, MastitisRecord $record)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'animal_id' => 'required|exists:animals,id',
            'test_result' => 'required|string|max:255',
            'treatment' => 'required|string|max:255',
            'recovery_status' => 'required|string|max:255',
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

        $record->update($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'Mastitis record updated successfully',
            'data' => $record->fresh(),
        ]);
    }

    public function dmiList($farmerId)
    {
        $rows = Animal::query()
            ->with(['animalType', 'farmer'])
            ->where('farmer_id', $farmerId)
            ->where('is_active', true)
            ->latest('id')
            ->get()
            ->map(function ($animal) {
                $latestMilk = MilkProduction::query()
                    ->where('animal_id', $animal->id)
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->first();

                $bodyWeight = (float) ($animal->weight ?? 0);
                $totalMilk = (float) ($latestMilk->total_milk ?? 0);
                $typeName = mb_strtolower(trim((string) ($animal->animalType->name ?? '')));
                $isMilking = (str_contains($typeName, 'milking') && ! str_contains($typeName, 'non'))
                    || ($typeName === '' && $totalMilk > 0);

                $requiredDmi = $isMilking
                    ? round(($bodyWeight * 0.02) + ($totalMilk * 0.33), 2)
                    : round(($bodyWeight * 0.025), 2);
                $displayDate = (! empty($latestMilk?->date))
                    ? Carbon::parse($latestMilk->date)->format('d/m/Y')
                    : now()->format('d/m/Y');

                return [
                    'id' => (int) $animal->id,
                    'animal_id' => (int) $animal->id,
                    'animal_name' => $animal->animal_name ?? '-',
                    'tag_number' => $animal->tag_number ?? '-',
                    'animal_type_name' => $animal->animalType->name ?? '-',
                    'dmi_type' => $isMilking ? 'Milking Cow' : 'Non Milking Cow',
                    'body_weight' => round($bodyWeight, 2),
                    'total_milk' => round($totalMilk, 2),
                    'required_dmi' => $requiredDmi,
                    'actual_dmi' => $requiredDmi,
                    'alert_status' => 'Auto Calculated',
                    'date' => $displayDate,
                    'notes' => '',
                ];
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
            : round($data['body_weight'] * 0.025, 2);
        $difference = round($data['actual_dmi'] - $requiredDmi, 2);
        $status = abs($difference) <= 0.5 ? 'Balanced' : ($difference < 0 ? 'Low' : 'High');

        $row = DmiRecord::create($data + [
            'required_dmi' => $requiredDmi,
            'alert_status' => $status,
        ]);

        return response()->json(['status' => true, 'message' => 'DMI record saved successfully', 'data' => $row], 201);
    }
}
