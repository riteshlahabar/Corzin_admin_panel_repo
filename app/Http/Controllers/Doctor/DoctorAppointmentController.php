<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Doctor\DoctorAppointment;
use Illuminate\Http\Request;

class DoctorAppointmentController extends Controller
{
    public function index(Request $request)
    {
        $query = DoctorAppointment::query()
            ->with('doctor')
            ->latest('requested_at')
            ->latest();

        if ($request->filled('search')) {
            $search = strtolower(trim((string) $request->search));
            $query->where(function ($builder) use ($search) {
                $builder->whereRaw('LOWER(farmer_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(animal_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(concern) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(status) LIKE ?', ["%{$search}%"]);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $appointments = $query->paginate(20)->withQueryString();

        $summaryQuery = DoctorAppointment::query();
        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'pending' => (clone $summaryQuery)->where('status', 'pending')->count(),
            'approved' => (clone $summaryQuery)->whereIn('status', ['approved', 'scheduled'])->count(),
            'completed' => (clone $summaryQuery)->where('status', 'completed')->count(),
        ];

        return view('doctor.appointments', compact('appointments', 'summary'));
    }
}

