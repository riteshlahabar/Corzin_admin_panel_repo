<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Dairy\Dairy;
use App\Models\Doctor\Doctor;
use App\Models\Doctor\DoctorAppointment;
use App\Models\Farmer\Animal;
use App\Models\Farmer\Farmer;
use App\Models\Farmer\MilkProduction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AnalyticsController extends Controller
{
    public function farmerAnalysis()
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $fromDate = $now->copy()->subMonths(11)->startOfMonth()->toDateString();
        $months = $this->monthKeys($now);

        $totalFarmers = Farmer::count();
        $activeFarmers = Schema::hasColumn('farmers', 'is_active')
            ? Farmer::where('is_active', true)->count()
            : $totalFarmers;
        $newFarmers = Farmer::whereBetween('created_at', [$monthStart, $monthEnd])->count();
        $farmersWithAnimals = Farmer::has('animals')->count();
        $farmersWithDairy = Farmer::has('dairies')->count();

        $activeRate = $totalFarmers > 0 ? round(($activeFarmers / $totalFarmers) * 100, 1) : 0;
        $animalCoverage = $totalFarmers > 0 ? round(($farmersWithAnimals / $totalFarmers) * 100, 1) : 0;
        $dairyCoverage = $totalFarmers > 0 ? round(($farmersWithDairy / $totalFarmers) * 100, 1) : 0;

        $farmerRegistrationsMap = Farmer::query()
            ->selectRaw("DATE_FORMAT(`created_at`, '%Y-%m') as month_key, COUNT(*) as total")
            ->whereDate('created_at', '>=', $fromDate)
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $animalAdditionsMap = Animal::query()
            ->selectRaw("DATE_FORMAT(`created_at`, '%Y-%m') as month_key, COUNT(*) as total")
            ->whereDate('created_at', '>=', $fromDate)
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $farmerGrowthSeries = $this->seriesFromMap($months, $farmerRegistrationsMap);
        $animalGrowthSeries = $this->seriesFromMap($months, $animalAdditionsMap);
        $monthLabels = $this->monthLabels($months);

        $topStates = Farmer::query()
            ->selectRaw("COALESCE(NULLIF(TRIM(state), ''), 'Unknown') as state_name, COUNT(*) as total")
            ->groupBy('state_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $farmerRows = Farmer::withCount(['animals', 'dairies'])
            ->latest()
            ->take(30)
            ->get()
            ->map(function (Farmer $farmer) {
                return [
                    'name' => trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) ?: '-',
                    'mobile' => $farmer->mobile ?: '-',
                    'city' => $farmer->city ?: '-',
                    'joined_at' => optional($farmer->created_at)->format('Y/m/d') ?: '-',
                    'completion' => $this->farmerCompletionPercent($farmer),
                    'animals_count' => (int) $farmer->animals_count,
                    'dairies_count' => (int) $farmer->dairies_count,
                ];
            });

        return view('analytics.farmer_analysis', compact(
            'totalFarmers',
            'activeFarmers',
            'newFarmers',
            'farmersWithAnimals',
            'farmersWithDairy',
            'activeRate',
            'animalCoverage',
            'dairyCoverage',
            'monthLabels',
            'farmerGrowthSeries',
            'animalGrowthSeries',
            'topStates',
            'farmerRows'
        ));
    }

    public function doctorAnalysis()
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $fromDate = $now->copy()->subMonths(11)->startOfMonth()->toDateString();
        $months = $this->monthKeys($now);

        $totalDoctors = Doctor::count();
        $approvedDoctors = Doctor::where('status', 'approved')->count();
        $pendingDoctors = Doctor::where('status', 'pending')->count();
        $rejectedDoctors = Doctor::whereIn('status', ['rejected', 'declined'])->count();
        $newDoctors = Doctor::whereBetween('created_at', [$monthStart, $monthEnd])->count();

        $doctorRegistrationsMap = Doctor::query()
            ->selectRaw("DATE_FORMAT(`created_at`, '%Y-%m') as month_key, COUNT(*) as total")
            ->whereDate('created_at', '>=', $fromDate)
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $doctorAppointmentsMap = DoctorAppointment::query()
            ->selectRaw("DATE_FORMAT(`created_at`, '%Y-%m') as month_key, COUNT(*) as total")
            ->whereDate('created_at', '>=', $fromDate)
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $doctorGrowthSeries = $this->seriesFromMap($months, $doctorRegistrationsMap);
        $appointmentGrowthSeries = $this->seriesFromMap($months, $doctorAppointmentsMap);
        $monthLabels = $this->monthLabels($months);

        $totalAppointments = DoctorAppointment::count();
        $completedAppointments = DoctorAppointment::where('status', 'completed')->count();
        $completionRate = $totalAppointments > 0 ? round(($completedAppointments / $totalAppointments) * 100, 1) : 0;
        $approvalRate = $totalDoctors > 0 ? round(($approvedDoctors / $totalDoctors) * 100, 1) : 0;

        $statusLabels = ['Approved', 'Pending', 'Rejected', 'Other'];
        $statusSeries = [
            $approvedDoctors,
            $pendingDoctors,
            $rejectedDoctors,
            max(0, $totalDoctors - ($approvedDoctors + $pendingDoctors + $rejectedDoctors)),
        ];

        $doctorRows = Doctor::withCount([
            'appointments',
            'appointments as completed_appointments_count' => function ($query) {
                $query->where('status', 'completed');
            },
        ])
            ->latest()
            ->take(30)
            ->get()
            ->map(function (Doctor $doctor) {
                $total = (int) $doctor->appointments_count;
                $completed = (int) $doctor->completed_appointments_count;
                $rate = $total > 0 ? round(($completed / $total) * 100) : 0;

                return [
                    'name' => $doctor->full_name ?: '-',
                    'contact' => $doctor->contact_number ?: '-',
                    'city' => $doctor->city ?: '-',
                    'status' => strtolower((string) ($doctor->status ?: 'pending')),
                    'joined_at' => optional($doctor->created_at)->format('Y/m/d') ?: '-',
                    'appointments_count' => $total,
                    'completion_rate' => $rate,
                ];
            });

        return view('analytics.doctor_analysis', compact(
            'totalDoctors',
            'approvedDoctors',
            'pendingDoctors',
            'rejectedDoctors',
            'newDoctors',
            'approvalRate',
            'totalAppointments',
            'completedAppointments',
            'completionRate',
            'monthLabels',
            'doctorGrowthSeries',
            'appointmentGrowthSeries',
            'statusLabels',
            'statusSeries',
            'doctorRows'
        ));
    }

    public function earnings()
    {
        $now = now();
        $months = $this->monthKeys($now);
        $fromDate = $now->copy()->subMonths(11)->startOfMonth()->toDateString();

        $farmerMap = MilkProduction::query()
            ->selectRaw("DATE_FORMAT(`date`, '%Y-%m') as month_key, COALESCE(SUM(total_milk * COALESCE(rate, 0)), 0) as total")
            ->whereDate('date', '>=', $fromDate)
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $doctorMap = DoctorAppointment::query()
            ->selectRaw("DATE_FORMAT(`created_at`, '%Y-%m') as month_key, COALESCE(SUM(COALESCE(charges, 0)), 0) as total")
            ->whereDate('created_at', '>=', $fromDate)
            ->whereNotNull('charges')
            ->whereNotIn('status', ['cancelled', 'declined', 'rejected'])
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $farmerSeries = $this->seriesFromMap($months, $farmerMap, true);
        $doctorSeries = $this->seriesFromMap($months, $doctorMap, true);
        $combinedSeries = collect($farmerSeries)
            ->zip($doctorSeries)
            ->map(fn ($pair) => round(((float) $pair[0]) + ((float) $pair[1]), 2))
            ->values()
            ->all();

        $monthLabels = $this->monthLabels($months);
        $totalFarmerEarning = array_sum($farmerSeries);
        $totalDoctorEarning = array_sum($doctorSeries);
        $totalCombinedEarning = array_sum($combinedSeries);

        $thisMonthFarmer = end($farmerSeries) ?: 0;
        $thisMonthDoctor = end($doctorSeries) ?: 0;
        $thisMonthCombined = end($combinedSeries) ?: 0;

        $previousMonthFarmer = count($farmerSeries) > 1 ? $farmerSeries[count($farmerSeries) - 2] : 0;
        $previousMonthDoctor = count($doctorSeries) > 1 ? $doctorSeries[count($doctorSeries) - 2] : 0;
        $previousMonthCombined = count($combinedSeries) > 1 ? $combinedSeries[count($combinedSeries) - 2] : 0;

        $farmerTrend = $this->percentChange((float) $thisMonthFarmer, (float) $previousMonthFarmer);
        $doctorTrend = $this->percentChange((float) $thisMonthDoctor, (float) $previousMonthDoctor);
        $combinedTrend = $this->percentChange((float) $thisMonthCombined, (float) $previousMonthCombined);

        $breakdownRows = collect($months)->map(function ($monthKey, $index) use ($monthLabels, $farmerSeries, $doctorSeries, $combinedSeries) {
            return [
                'month' => $monthLabels[$index],
                'farmer' => $farmerSeries[$index],
                'doctor' => $doctorSeries[$index],
                'combined' => $combinedSeries[$index],
            ];
        })->reverse()->values();

        $topDoctorEarnings = DoctorAppointment::query()
            ->leftJoin('doctors', 'doctors.id', '=', 'doctor_appointments.doctor_id')
            ->selectRaw("TRIM(CONCAT(COALESCE(doctors.first_name, ''), ' ', COALESCE(doctors.last_name, ''))) as label, COALESCE(SUM(COALESCE(doctor_appointments.charges, 0)), 0) as total")
            ->whereNotNull('doctor_appointments.charges')
            ->whereNotIn('doctor_appointments.status', ['cancelled', 'declined', 'rejected'])
            ->groupByRaw("TRIM(CONCAT(COALESCE(doctors.first_name, ''), ' ', COALESCE(doctors.last_name, '')))")
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'label' => trim((string) $row->label) !== '' ? $row->label : 'Unknown Doctor',
                'total' => (float) $row->total,
            ]);

        $topDairyEarnings = MilkProduction::query()
            ->leftJoin('dairies', 'dairies.id', '=', 'milk_productions.dairy_id')
            ->selectRaw("COALESCE(NULLIF(TRIM(dairies.dairy_name), ''), 'Unknown Dairy') as label, COALESCE(SUM(milk_productions.total_milk * COALESCE(milk_productions.rate, 0)), 0) as total")
            ->groupByRaw("COALESCE(NULLIF(TRIM(dairies.dairy_name), ''), 'Unknown Dairy')")
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'total' => (float) $row->total,
            ]);

        return view('analytics.earnings', compact(
            'monthLabels',
            'farmerSeries',
            'doctorSeries',
            'combinedSeries',
            'totalFarmerEarning',
            'totalDoctorEarning',
            'totalCombinedEarning',
            'thisMonthFarmer',
            'thisMonthDoctor',
            'thisMonthCombined',
            'farmerTrend',
            'doctorTrend',
            'combinedTrend',
            'breakdownRows',
            'topDoctorEarnings',
            'topDairyEarnings'
        ));
    }

    private function monthKeys(Carbon $now): Collection
    {
        return collect(range(11, 0))
            ->map(fn ($offset) => $now->copy()->subMonths($offset)->format('Y-m'))
            ->values();
    }

    private function monthLabels(Collection $monthKeys): array
    {
        return $monthKeys
            ->map(fn ($key) => Carbon::createFromFormat('Y-m', $key)->format('M Y'))
            ->values()
            ->all();
    }

    private function seriesFromMap(Collection $monthKeys, $map, bool $float = false): array
    {
        return $monthKeys->map(function ($monthKey) use ($map, $float) {
            $value = $map[$monthKey] ?? 0;
            return $float ? round((float) $value, 2) : (int) $value;
        })->values()->all();
    }

    private function farmerCompletionPercent(Farmer $farmer): int
    {
        $fields = [
            $farmer->mobile,
            $farmer->first_name,
            $farmer->last_name,
            $farmer->village,
            $farmer->city,
            $farmer->taluka,
            $farmer->district,
            $farmer->state,
            $farmer->pincode,
        ];

        $filled = collect($fields)->filter(fn ($value) => trim((string) $value) !== '')->count();

        return (int) round(($filled / max(count($fields), 1)) * 100);
    }

    private function percentChange(float $current, float $previous): float
    {
        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}

