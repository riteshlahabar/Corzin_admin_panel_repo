<?php

namespace App\Http\Controllers;

use App\Models\Dairy\Dairy;
use App\Models\Doctor\Doctor;
use App\Models\Doctor\DoctorAppointment;
use App\Models\Doctor\DoctorSubscription;
use App\Models\Farmer\Animal;
use App\Models\Farmer\Farmer;
use App\Models\Farmer\FarmerSubscription;
use App\Models\Farmer\MilkProduction;
use App\Models\Shop\ShopProduct;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $prevMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $prevMonthEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();

        $totalFarmers = Farmer::count();
        $totalDoctors = Doctor::count();
        $totalAnimals = Animal::count();
        $totalAppointments = DoctorAppointment::count();
        $totalDairies = Dairy::count();

        $thisMonthFarmers = Farmer::whereBetween('created_at', [$monthStart, $monthEnd])->count();
        $prevMonthFarmers = Farmer::whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])->count();
        $farmersTrend = $this->percentChange($thisMonthFarmers, $prevMonthFarmers);

        $thisMonthDoctors = Doctor::whereBetween('created_at', [$monthStart, $monthEnd])->count();
        $prevMonthDoctors = Doctor::whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])->count();
        $doctorsTrend = $this->percentChange($thisMonthDoctors, $prevMonthDoctors);

        $thisMonthAppointments = DoctorAppointment::whereBetween('created_at', [$monthStart, $monthEnd])->count();
        $prevMonthAppointments = DoctorAppointment::whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])->count();
        $appointmentsTrend = $this->percentChange($thisMonthAppointments, $prevMonthAppointments);

        $thisMonthVisitAvg = (float) DoctorAppointment::whereBetween('created_at', [$monthStart, $monthEnd])
            ->whereNotNull('charges')
            ->avg('charges');
        $prevMonthVisitAvg = (float) DoctorAppointment::whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
            ->whereNotNull('charges')
            ->avg('charges');
        $visitAvgTrend = $this->percentChange($thisMonthVisitAvg, $prevMonthVisitAvg);

        $series = $this->monthlyRevenueSeries($now);
        $monthlyLabels = $series['labels'];
        $monthlyMilkRevenue = $series['milk'];
        $monthlyVisitRevenue = $series['visits'];
        $monthlyTotalRevenue = $series['total'];
        $totalRevenue = array_sum($monthlyTotalRevenue);
        $thisMonthRevenue = end($monthlyTotalRevenue) ?: 0;
        $prevMonthRevenue = count($monthlyTotalRevenue) >= 2 ? $monthlyTotalRevenue[count($monthlyTotalRevenue) - 2] : 0;
        $revenueTrend = $this->percentChange($thisMonthRevenue, $prevMonthRevenue);

        $activeSubscriptions = $this->activeSubscriptionsCount();
        $distributionLabels = ['Farmers', 'Doctors', 'Animals', 'Dairies'];
        $distributionSeries = [$totalFarmers, $totalDoctors, $totalAnimals, $totalDairies];

        $topStates = Farmer::query()
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
        $topMilkProducerRow = MilkProduction::query()
            ->join('animals', 'animals.id', '=', 'milk_productions.animal_id')
            ->selectRaw('animals.farmer_id as farmer_id, COALESCE(SUM(milk_productions.total_milk), 0) as total_milk')
            ->whereNotNull('animals.farmer_id')
            ->groupBy('animals.farmer_id')
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

        $recentActivities = $this->recentActivities();

        return view('dashboard_v2', compact(
            'totalRevenue',
            'thisMonthRevenue',
            'revenueTrend',
            'totalAppointments',
            'thisMonthAppointments',
            'appointmentsTrend',
            'activeSubscriptions',
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
            'recentActivities'
        ));
    }

    private function monthlyRevenueSeries(Carbon $now): array
    {
        $fromDate = $now->copy()->subMonths(11)->startOfMonth()->toDateString();
        $months = collect(range(11, 0))
            ->map(fn ($offset) => $now->copy()->subMonths($offset)->format('Y-m'))
            ->values();

        $milkByMonth = MilkProduction::query()
            ->selectRaw("DATE_FORMAT(`date`, '%Y-%m') as month_key, COALESCE(SUM(total_milk * COALESCE(rate, 0)), 0) as revenue")
            ->whereDate('date', '>=', $fromDate)
            ->groupBy('month_key')
            ->pluck('revenue', 'month_key');

        $visitByMonth = DoctorAppointment::query()
            ->selectRaw("DATE_FORMAT(`created_at`, '%Y-%m') as month_key, COALESCE(SUM(COALESCE(charges, 0)), 0) as revenue")
            ->whereDate('created_at', '>=', $fromDate)
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
