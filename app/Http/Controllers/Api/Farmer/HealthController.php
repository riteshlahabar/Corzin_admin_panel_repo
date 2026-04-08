<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\DmiRecord;
use App\Models\Farmer\MastitisRecord;
use App\Models\Farmer\MedicalRecord;
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
                'date' => optional($row->date)->format('d/m/Y'),
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
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }

        $row = MastitisRecord::create($validator->validated());
        return response()->json(['status' => true, 'message' => 'Mastitis record saved successfully', 'data' => $row], 201);
    }

    public function dmiList($farmerId)
    {
        $rows = DmiRecord::with('animal')
            ->where('farmer_id', $farmerId)
            ->latest('date')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'animal_id' => $row->animal_id,
                'animal_name' => $row->animal->animal_name ?? '-',
                'tag_number' => $row->animal->tag_number ?? '-',
                'body_weight' => $row->body_weight,
                'total_milk' => $row->total_milk,
                'required_dmi' => $row->required_dmi,
                'actual_dmi' => $row->actual_dmi,
                'alert_status' => $row->alert_status,
                'date' => optional($row->date)->format('d/m/Y'),
                'notes' => $row->notes,
            ]);

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
        $requiredDmi = round(($data['body_weight'] * 0.02) + ($data['total_milk'] * 0.33), 2);
        $difference = round($data['actual_dmi'] - $requiredDmi, 2);
        $status = abs($difference) <= 0.5 ? 'Balanced' : ($difference < 0 ? 'Low' : 'High');

        $row = DmiRecord::create($data + [
            'required_dmi' => $requiredDmi,
            'alert_status' => $status,
        ]);

        return response()->json(['status' => true, 'message' => 'DMI record saved successfully', 'data' => $row], 201);
    }
}
