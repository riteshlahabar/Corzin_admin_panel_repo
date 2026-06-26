<?php

namespace App\Http\Controllers;

use App\Models\Dairy\Dairy;
use App\Models\Doctor\Doctor;
use App\Models\Doctor\DoctorAppointment;
use App\Models\Doctor\DoctorSubscription;
use App\Models\Farmer\Animal;
use App\Models\Farmer\AnimalType;
use App\Models\Farmer\Farmer;
use App\Models\Farmer\FarmerSubscription;
use App\Models\Farmer\MilkProduction;
use App\Models\Shop\ShopProduct;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $now = now();
        [$selectedFromDate, $selectedToDate, $currentStart, $currentEnd, $previousStart, $previousEnd, $hasCustomRange] = $this->resolveDashboardRange($request, $now);
        $trendLabel = $hasCustomRange ? 'vs previous range' : 'vs last month';

        $totalFarmers = $this->countForDashboard(Farmer::query(), 'created_at', $hasCustomRange, $currentStart, $currentEnd);
        $totalDoctors = $this->countForDashboard(Doctor::query(), 'created_at', $hasCustomRange, $currentStart, $currentEnd);
        $totalAnimals = $this->countForDashboard(Animal::query(), 'created_at', $hasCustomRange, $currentStart, $currentEnd);
        $totalAppointments = $this->countForDashboard(DoctorAppointment::query(), 'created_at', $hasCustomRange, $currentStart, $currentEnd);
        $totalDairies = $this->countForDashboard(Dairy::query(), 'created_at', $hasCustomRange, $currentStart, $currentEnd);

        $thisMonthFarmers = Farmer::whereBetween('created_at', [$currentStart, $currentEnd])->count();
        $prevMonthFarmers = Farmer::whereBetween('created_at', [$previousStart, $previousEnd])->count();
        $farmersTrend = $this->percentChange($thisMonthFarmers, $prevMonthFarmers);

        $thisMonthDoctors = Doctor::whereBetween('created_at', [$currentStart, $currentEnd])->count();
        $prevMonthDoctors = Doctor::whereBetween('created_at', [$previousStart, $previousEnd])->count();
        $doctorsTrend = $this->percentChange($thisMonthDoctors, $prevMonthDoctors);

        $thisMonthAppointments = DoctorAppointment::whereBetween('created_at', [$currentStart, $currentEnd])->count();
        $prevMonthAppointments = DoctorAppointment::whereBetween('created_at', [$previousStart, $previousEnd])->count();
        $appointmentsTrend = $this->percentChange($thisMonthAppointments, $prevMonthAppointments);

        $thisMonthVisitAvg = (float) DoctorAppointment::whereBetween('created_at', [$currentStart, $currentEnd])
            ->whereNotNull('charges')
            ->avg('charges');
        $prevMonthVisitAvg = (float) DoctorAppointment::whereBetween('created_at', [$previousStart, $previousEnd])
            ->whereNotNull('charges')
            ->avg('charges');
        $visitAvgTrend = $this->percentChange($thisMonthVisitAvg, $prevMonthVisitAvg);

        $series = $this->monthlyRevenueSeries($now, $selectedFromDate, $selectedToDate);
        $monthlyLabels = $series['labels'];
        $monthlyMilkRevenue = $series['milk'];
        $monthlyVisitRevenue = $series['visits'];
        $monthlyTotalRevenue = $series['total'];
        $totalRevenue = array_sum($monthlyTotalRevenue);
        $thisMonthRevenue = end($monthlyTotalRevenue) ?: 0;
        $prevMonthRevenue = count($monthlyTotalRevenue) >= 2 ? $monthlyTotalRevenue[count($monthlyTotalRevenue) - 2] : 0;
        $revenueTrend = $this->percentChange($thisMonthRevenue, $prevMonthRevenue);

        $activeSubscriptions = $this->activeSubscriptionsCount();
        $farmerActiveSubscriptions = $this->activeFarmerSubscriptionsCount($selectedFromDate, $selectedToDate);
        $previousFarmerActiveSubscriptions = $this->activeFarmerSubscriptionsCount($previousStart, $previousEnd);
        $farmerActiveSubscriptionTrend = $this->percentChange($farmerActiveSubscriptions, $previousFarmerActiveSubscriptions);
        $farmerSubscriptionRevenue = $this->farmerSubscriptionRevenue($selectedFromDate, $selectedToDate);
        $previousFarmerSubscriptionRevenue = $this->farmerSubscriptionRevenue($previousStart, $previousEnd);
        $farmerSubscriptionRevenueTrend = $this->percentChange($farmerSubscriptionRevenue, $previousFarmerSubscriptionRevenue);
        $distributionLabels = ['Farmers', 'Doctors', 'Animals', 'Dairies'];
        $distributionSeries = [$totalFarmers, $totalDoctors, $totalAnimals, $totalDairies];
        $animalTypeChart = $this->animalTypeTalukaChart($selectedFromDate, $selectedToDate);

        $topStatesQuery = Farmer::query();
        if ($hasCustomRange) {
            $topStatesQuery->whereBetween('created_at', [$currentStart, $currentEnd]);
        }

        $topStates = $topStatesQuery
            ->selectRaw("COALESCE(NULLIF(TRIM(state), ''), 'Unknown') as state_name, COUNT(*) as total")
            ->groupBy('state_name')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(function ($row) use ($totalFarmers) {
                $count = (int) $row->total;
                return [
                    'name' => $row->state_name,
                    'total' => $count,
                    'percent' => $totalFarmers > 0 ? round(($count / $totalFarmers) * 100, 1) : 0,
                ];
            });

        $topMilkProducer = null;
        $topMilkProducerQuery = MilkProduction::query()
            ->join('animals', 'animals.id', '=', 'milk_productions.animal_id')
            ->selectRaw('animals.farmer_id as farmer_id, COALESCE(SUM(milk_productions.total_milk), 0) as total_milk')
            ->whereNotNull('animals.farmer_id')
            ->groupBy('animals.farmer_id');
        if ($hasCustomRange) {
            $topMilkProducerQuery->whereBetween('milk_productions.date', [$selectedFromDate->toDateString(), $selectedToDate->toDateString()]);
        }
        $topMilkProducerRow = $topMilkProducerQuery
            ->orderByDesc('total_milk')
            ->first();

        if ($topMilkProducerRow && (int) $topMilkProducerRow->farmer_id > 0) {
            $topFarmer = Farmer::find((int) $topMilkProducerRow->farmer_id);
            if ($topFarmer) {
                $name = trim(($topFarmer->first_name ?? '').' '.($topFarmer->last_name ?? ''));
                $topMilkProducer = [
                    'name' => $name !== '' ? $name : 'Farmer #'.$topFarmer->id,
                    'milk' => round((float) ($topMilkProducerRow->total_milk ?? 0), 2),
                ];
            }
        }

        $popularProducts = ShopProduct::query()
            ->latest('updated_at')
            ->take(6)
            ->get();

        return view('dashboard_v2', compact(
            'totalRevenue',
            'thisMonthRevenue',
            'revenueTrend',
            'totalAppointments',
            'thisMonthAppointments',
            'appointmentsTrend',
            'activeSubscriptions',
            'farmerActiveSubscriptions',
            'farmerSubscriptionRevenue',
            'farmerActiveSubscriptionTrend',
            'farmerSubscriptionRevenueTrend',
            'totalFarmers',
            'thisMonthFarmers',
            'farmersTrend',
            'totalDoctors',
            'thisMonthDoctors',
            'doctorsTrend',
            'thisMonthVisitAvg',
            'visitAvgTrend',
            'monthlyLabels',
            'monthlyMilkRevenue',
            'monthlyVisitRevenue',
            'monthlyTotalRevenue',
            'distributionLabels',
            'distributionSeries',
            'topStates',
            'topMilkProducer',
            'popularProducts',
            'trendLabel',
            'selectedFromDate',
            'selectedToDate',
            'animalTypeChart'
        ));
    }

    private function monthlyRevenueSeries(Carbon $now, ?Carbon $fromDate = null, ?Carbon $toDate = null): array
    {
        if ($fromDate && $toDate) {
            $queryFromDate = $fromDate->copy()->startOfDay()->toDateString();
            $queryToDate = $toDate->copy()->endOfDay()->toDateString();
            $cursor = $fromDate->copy()->startOfMonth();
            $lastMonth = $toDate->copy()->startOfMonth();
            $months = collect();
            while ($cursor->lte($lastMonth)) {
                $months->push($cursor->format('Y-m'));
                $cursor->addMonth();
            }
        } else {
            $queryFromDate = $now->copy()->subMonths(11)->startOfMonth()->toDateString();
            $queryToDate = $now->copy()->endOfMonth()->toDateString();
            $months = collect(range(11, 0))
                ->map(fn ($offset) => $now->copy()->subMonths($offset)->format('Y-m'))
                ->values();
        }

        $milkByMonth = MilkProduction::query()
            ->selectRaw("DATE_FORMAT(`date`, '%Y-%m') as month_key, COALESCE(SUM(total_milk * COALESCE(rate, 0)), 0) as revenue")
            ->whereBetween('date', [$queryFromDate, $queryToDate])
            ->groupBy('month_key')
            ->pluck('revenue', 'month_key');

        $visitByMonth = DoctorAppointment::query()
            ->selectRaw("DATE_FORMAT(`created_at`, '%Y-%m') as month_key, COALESCE(SUM(COALESCE(charges, 0)), 0) as revenue")
            ->whereBetween('created_at', [$queryFromDate, $queryToDate])
            ->groupBy('month_key')
            ->pluck('revenue', 'month_key');

        $labels = [];
        $milk = [];
        $visits = [];
        $total = [];

        foreach ($months as $monthKey) {
            $monthDate = Carbon::createFromFormat('Y-m', $monthKey);
            $labels[] = $monthDate->format('M Y');
            $milkValue = (float) ($milkByMonth[$monthKey] ?? 0);
            $visitValue = (float) ($visitByMonth[$monthKey] ?? 0);
            $milk[] = round($milkValue, 2);
            $visits[] = round($visitValue, 2);
            $total[] = round($milkValue + $visitValue, 2);
        }

        return [
            'labels' => $labels,
            'milk' => $milk,
            'visits' => $visits,
            'total' => $total,
        ];
    }

    private function activeSubscriptionsCount(): int
    {
        $today = now()->toDateString();
        $count = 0;

        if (Schema::hasTable('farmer_subscriptions')) {
            $count += FarmerSubscription::query()
                ->where('status', 'active')
                ->where(function ($query) use ($today) {
                    $query->whereNull('due_date')
                        ->orWhereDate('due_date', '>=', $today);
                })
                ->count();
        }

        if (Schema::hasTable('doctor_subscriptions')) {
            $count += DoctorSubscription::query()
                ->where('status', 'active')
                ->where(function ($query) use ($today) {
                    $query->whereNull('due_date')
                        ->orWhereDate('due_date', '>=', $today);
                })
                ->count();
        }

        return $count;
    }

    private function recentActivities(): Collection
    {
        $activities = collect();

        Farmer::query()->latest()->take(3)->get()->each(function (Farmer $farmer) use ($activities) {
            $name = trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) ?: 'Farmer';
            $activities->push([
                'icon' => 'iconoir-user-plus',
                'text' => "New farmer registered: {$name}",
                'time' => $farmer->created_at,
            ]);
        });

        Animal::query()->latest()->take(3)->get()->each(function (Animal $animal) use ($activities) {
            $animalName = $animal->animal_name ?: 'Animal';
            $activities->push([
                'icon' => 'iconoir-paw',
                'text' => "Animal added: {$animalName}",
                'time' => $animal->created_at,
            ]);
        });

        MilkProduction::query()->latest('date')->take(3)->get()->each(function (MilkProduction $milk) use ($activities) {
            $liters = (float) ($milk->total_milk ?? 0);
            $activities->push([
                'icon' => 'iconoir-droplet',
                'text' => 'Milk entry updated: '.number_format($liters, 1).' L',
                'time' => $milk->created_at,
            ]);
        });

        DoctorAppointment::query()->latest()->take(3)->get()->each(function (DoctorAppointment $appointment) use ($activities) {
            $farmer = $appointment->farmer_name ?: 'Farmer';
            $status = ucfirst($appointment->status ?: 'pending');
            $activities->push([
                'icon' => 'iconoir-calendar',
                'text' => "Doctor visit {$status}: {$farmer}",
                'time' => $appointment->created_at,
            ]);
        });

        ShopProduct::query()->latest()->take(3)->get()->each(function (ShopProduct $product) use ($activities) {
            $activities->push([
                'icon' => 'iconoir-cart',
                'text' => "Shop product updated: {$product->name}",
                'time' => $product->updated_at ?? $product->created_at,
            ]);
        });

        return $activities
            ->sortByDesc(fn ($item) => $item['time']?->timestamp ?? 0)
            ->values()
            ->take(8);
    }

    private function activeFarmerSubscriptionsCount(?Carbon $fromDate = null, ?Carbon $toDate = null): int
    {
        $today = now()->toDateString();

        if (! Schema::hasTable('farmer_subscriptions')) {
            return 0;
        }

        $query = FarmerSubscription::query()
            ->where('status', 'active')
            ->where(function ($query) use ($today) {
                $query->whereNull('due_date')
                    ->orWhereDate('due_date', '>=', $today);
            });

        if ($fromDate && $toDate) {
            $query->whereBetween('start_date', [$fromDate->toDateString(), $toDate->toDateString()]);
        }

        return $query->count();
    }

    private function farmerSubscriptionRevenue(?Carbon $fromDate = null, ?Carbon $toDate = null): float
    {
        if (! Schema::hasTable('farmer_subscriptions') || ! Schema::hasTable('farmer_plans')) {
            return 0;
        }

        $query = FarmerSubscription::query()
            ->join('farmer_plans', 'farmer_plans.id', '=', 'farmer_subscriptions.farmer_plan_id')
            ->selectRaw('COALESCE(SUM(farmer_plans.price), 0) as total');

        if ($fromDate && $toDate) {
            $query->whereBetween('farmer_subscriptions.start_date', [$fromDate->toDateString(), $toDate->toDateString()]);
        }

        return (float) $query->value('total');
    }

    private function animalTypeTalukaChart(?Carbon $fromDate = null, ?Carbon $toDate = null): array
    {
        $types = AnimalType::query()
            ->orderBy('id')
            ->take(4)
            ->pluck('name')
            ->filter(fn ($name) => trim((string) $name) !== '')
            ->values();

        $query = Animal::query()
            ->join('farmers', 'farmers.id', '=', 'animals.farmer_id')
            ->join('animal_types', 'animal_types.id', '=', 'animals.animal_type_id')
            ->selectRaw("COALESCE(NULLIF(TRIM(farmers.taluka), ''), 'Unknown') as taluka_name, animal_types.name as animal_type_name, COUNT(*) as total");

        if ($fromDate && $toDate) {
            $query->whereBetween('animals.created_at', [$fromDate->copy()->startOfDay(), $toDate->copy()->endOfDay()]);
        }

        if ($types->isNotEmpty()) {
            $query->whereIn('animal_types.name', $types->all());
        }

        $rows = $query
            ->groupBy('taluka_name', 'animal_type_name')
            ->orderBy('taluka_name')
            ->get();

        $labels = $rows->pluck('taluka_name')->unique()->values();
        $series = $types->map(function ($type) use ($rows, $labels) {
            $typeRows = $rows->where('animal_type_name', $type);

            return [
                'name' => $type,
                'data' => $labels->map(function ($label) use ($typeRows) {
                    $match = $typeRows->firstWhere('taluka_name', $label);
                    return (int) ($match->total ?? 0);
                })->values()->all(),
            ];
        })->values()->all();

        return [
            'labels' => $labels->all(),
            'series' => $series,
            'height' => max(320, count($labels) * 42),
        ];
    }

    private function countForDashboard($query, string $column, bool $hasCustomRange, Carbon $currentStart, Carbon $currentEnd): int
    {
        if ($hasCustomRange) {
            $query->whereBetween($column, [$currentStart, $currentEnd]);
        }

        return $query->count();
    }

    private function resolveDashboardRange(Request $request, Carbon $now): array
    {
        $fromInput = trim((string) $request->query('from_date', ''));
        $toInput = trim((string) $request->query('to_date', ''));

        $selectedFromDate = $fromInput !== '' ? Carbon::parse($fromInput)->startOfDay() : null;
        $selectedToDate = $toInput !== '' ? Carbon::parse($toInput)->endOfDay() : null;

        if ($selectedFromDate && $selectedToDate && $selectedFromDate->gt($selectedToDate)) {
            [$selectedFromDate, $selectedToDate] = [$selectedToDate->copy()->startOfDay(), $selectedFromDate->copy()->endOfDay()];
        }

        if (! $selectedFromDate && $selectedToDate) {
            $selectedFromDate = $selectedToDate->copy()->startOfDay();
        }

        if ($selectedFromDate && ! $selectedToDate) {
            $selectedToDate = $selectedFromDate->copy()->endOfDay();
        }

        $hasCustomRange = $selectedFromDate !== null && $selectedToDate !== null;

        if ($hasCustomRange) {
            $currentStart = $selectedFromDate->copy()->startOfDay();
            $currentEnd = $selectedToDate->copy()->endOfDay();
            $days = max(1, $currentStart->diffInDays($currentEnd) + 1);
            $previousEnd = $currentStart->copy()->subDay()->endOfDay();
            $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();
        } else {
            $currentStart = $now->copy()->startOfMonth();
            $currentEnd = $now->copy()->endOfMonth();
            $previousStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
            $previousEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();
        }

        return [
            $selectedFromDate?->copy()->startOfDay(),
            $selectedToDate?->copy()->endOfDay(),
            $currentStart,
            $currentEnd,
            $previousStart,
            $previousEnd,
            $hasCustomRange,
        ];
    }

    private function percentChange(float|int $current, float|int $previous): float
    {
        $previous = (float) $previous;
        $current = (float) $current;

        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
