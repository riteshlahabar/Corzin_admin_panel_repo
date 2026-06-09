<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Doctor\DoctorAppointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorVisitedController extends Controller
{
    public function index(Request $request)
    {
        $query = DoctorAppointment::query()
            ->with(['doctor', 'farmer'])
            ->where('status', 'completed')
            ->latest('completed_at')
            ->latest();

        $search = strtolower(trim((string) $request->query('search', '')));
        $searchField = strtolower(trim((string) $request->query('search_field', 'all')));
        $fromDate = trim((string) $request->query('from_date', ''));
        $toDate = trim((string) $request->query('to_date', ''));

        if ($search !== '') {
            $this->applySearchFilter($query, $search, $searchField);
        }

        if ($fromDate !== '') {
            $query->whereDate(DB::raw('COALESCE(completed_at, updated_at)'), '>=', $fromDate);
        }

        if ($toDate !== '') {
            $query->whereDate(DB::raw('COALESCE(completed_at, updated_at)'), '<=', $toDate);
        }

        $visits = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('doctor.visited', compact('visits'));
    }

    private function applySearchFilter($query, string $search, string $searchField): void
    {
        if ($searchField === 'farmer_name') {
            $query->where(function ($builder) use ($search) {
                $builder->whereRaw('LOWER(COALESCE(farmer_name, "")) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('farmer', function ($farmerQuery) use ($search) {
                        $farmerQuery->whereRaw('LOWER(CONCAT_WS(" ", COALESCE(first_name, ""), COALESCE(middle_name, ""), COALESCE(last_name, ""))) LIKE ?', ["%{$search}%"]);
                    });
            });

            return;
        }

        if ($searchField === 'doctor_name') {
            $query->whereHas('doctor', function ($doctorQuery) use ($search) {
                $doctorQuery->whereRaw('LOWER(CONCAT_WS(" ", COALESCE(first_name, ""), COALESCE(last_name, ""))) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(COALESCE(name, "")) LIKE ?', ["%{$search}%"]);
            });

            return;
        }

        if ($searchField === 'animal_name') {
            $query->whereRaw('LOWER(COALESCE(animal_name, "")) LIKE ?', ["%{$search}%"]);
            return;
        }

        if ($searchField === 'concern') {
            $query->whereRaw('LOWER(COALESCE(concern, "")) LIKE ?', ["%{$search}%"]);
            return;
        }

        if ($searchField === 'medicine') {
            $query->whereRaw('LOWER(COALESCE(treatment_details, "")) LIKE ?', ["%{$search}%"]);
            return;
        }

        if ($searchField === 'onsite_treatment') {
            $query->whereRaw('LOWER(COALESCE(onsite_treatment, "")) LIKE ?', ["%{$search}%"]);
            return;
        }

        if ($searchField === 'completed_date') {
            $query->where(function ($builder) use ($search) {
                $builder->whereRaw('LOWER(DATE_FORMAT(COALESCE(completed_at, updated_at), "%d-%m-%Y %h:%i %p")) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(DATE_FORMAT(COALESCE(completed_at, updated_at), "%Y-%m-%d")) LIKE ?', ["%{$search}%"]);
            });

            return;
        }

        if ($searchField === 'charges') {
            $query->whereRaw('LOWER(CAST(COALESCE(charges, "") AS CHAR)) LIKE ?', ["%{$search}%"]);
            return;
        }

        $query->where(function ($builder) use ($search) {
            $builder->whereRaw('LOWER(COALESCE(farmer_name, "")) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(COALESCE(animal_name, "")) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(COALESCE(concern, "")) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(COALESCE(treatment_details, "")) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(COALESCE(onsite_treatment, "")) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(CAST(COALESCE(charges, "") AS CHAR)) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(DATE_FORMAT(COALESCE(completed_at, updated_at), "%d-%m-%Y %h:%i %p")) LIKE ?', ["%{$search}%"])
                ->orWhereHas('doctor', function ($doctorQuery) use ($search) {
                    $doctorQuery->whereRaw('LOWER(CONCAT_WS(" ", COALESCE(first_name, ""), COALESCE(last_name, ""))) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(COALESCE(name, "")) LIKE ?', ["%{$search}%"]);
                })
                ->orWhereHas('farmer', function ($farmerQuery) use ($search) {
                    $farmerQuery->whereRaw('LOWER(CONCAT_WS(" ", COALESCE(first_name, ""), COALESCE(middle_name, ""), COALESCE(last_name, ""))) LIKE ?', ["%{$search}%"]);
                });
        });
    }
}
