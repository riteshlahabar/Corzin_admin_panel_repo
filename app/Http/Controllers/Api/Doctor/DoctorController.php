<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Doctor\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $query = Doctor::query()
            ->where('status', 'approved')
            ->latest();

        if ($request->filled('search')) {
            $search = strtolower(trim((string) $request->search));
            $query->where(function ($builder) use ($search) {
                $builder->whereRaw('LOWER(first_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(degree) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(city) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(contact_number) LIKE ?', ["%{$search}%"]);
            });
        }

        $doctors = $query->get()->map(function (Doctor $doctor) {
            return [
                'id' => $doctor->id,
                'name' => $doctor->full_name,
                'speciality' => $doctor->degree ?? '',
                'location' => $doctor->city ?? '',
                'phone' => $doctor->contact_number ?? '',
                'experience' => '',
                'available_today' => true,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Doctors fetched successfully',
            'data' => $doctors,
        ]);
    }
}
