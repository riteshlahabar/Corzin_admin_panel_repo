<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Doctor\Doctor;
use Illuminate\Http\Request;

class DoctorRatingController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $query = Doctor::query()
            ->withCount('ratings')
            ->withAvg('ratings as average_rating', 'rating');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%")
                    ->orWhere('degree', 'like', "%{$search}%")
                    ->orWhere('clinic_name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $doctors = $query
            ->orderByDesc('average_rating')
            ->orderByDesc('ratings_count')
            ->orderBy('first_name')
            ->paginate($this->tablePerPage($request))
            ->withQueryString();

        $summary = [
            'rated_doctors' => Doctor::query()->has('ratings')->count(),
            'total_ratings' => (int) Doctor::query()->withCount('ratings')->get()->sum('ratings_count'),
            'average_rating' => round((float) Doctor::query()->withAvg('ratings as average_rating', 'rating')->get()->avg('average_rating'), 1),
        ];

        return view('doctor.ratings', compact('doctors', 'summary', 'search'));
    }
}
