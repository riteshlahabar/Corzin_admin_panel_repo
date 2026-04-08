<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Doctor\DoctorAppointment;
use Illuminate\Http\Request;

class DoctorVisitedController extends Controller
{
    public function index(Request $request)
    {
        $query = DoctorAppointment::query()
            ->with('doctor')
            ->where('status', 'completed')
            ->latest('completed_at')
            ->latest();

        if ($request->filled('search')) {
            $search = strtolower(trim((string) $request->search));
            $query->where(function ($builder) use ($search) {
                $builder->whereRaw('LOWER(farmer_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(animal_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(concern) LIKE ?', ["%{$search}%"]);
            });
        }

        $visits = $query->paginate(20)->withQueryString();

        return view('doctor.visited', compact('visits'));
    }
}

