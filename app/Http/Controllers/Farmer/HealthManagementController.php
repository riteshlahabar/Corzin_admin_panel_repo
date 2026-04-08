<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Animal;
use App\Models\Farmer\DmiRecord;
use App\Models\Farmer\Farmer;
use App\Models\Farmer\MastitisRecord;
use App\Models\Farmer\MedicalRecord;
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
            'farmer_id' => 'required|exists:farmers,id',
            'animal_id' => 'required|exists:animals,id',
            'test_result' => 'required|string|max:255',
            'treatment' => 'required|string|max:255',
            'recovery_status' => 'required|string|max:255',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        MastitisRecord::create($data);

        return redirect()->route('health.mastitis')->with('success', 'Mastitis record added successfully.');
    }

    public function dmi()
    {
        $rows = DmiRecord::with(['farmer', 'animal'])->latest('date')->get();
        return view('health.dmi', [
            'title' => 'DMI Calculator',
            'rows' => $rows,
            'farmers' => Farmer::orderBy('first_name')->get(),
            'animals' => Animal::with('farmer')->orderBy('animal_name')->get(),
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

        $requiredDmi = round(($data['body_weight'] * 0.02) + ($data['total_milk'] * 0.33), 2);
        $difference = round($data['actual_dmi'] - $requiredDmi, 2);
        $status = abs($difference) <= 0.5 ? 'Balanced' : ($difference < 0 ? 'Low' : 'High');

        DmiRecord::create($data + [
            'required_dmi' => $requiredDmi,
            'alert_status' => $status,
        ]);

        return redirect()->route('health.dmi')->with('success', 'DMI record added successfully.');
    }
}
