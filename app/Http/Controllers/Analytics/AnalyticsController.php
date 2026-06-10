<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Dairy\Dairy;
use App\Models\Dairy\DairyPaymentEntry;
use App\Models\Doctor\Doctor;
use App\Models\Doctor\DoctorAppointment;
use App\Models\Farmer\Animal;
use App\Models\Farmer\AnimalPregnancy;
use App\Models\Farmer\DmiRecord;
use App\Models\Farmer\Farmer;
use App\Models\Farmer\FeedingRecord;
use App\Models\Farmer\MastitisRecord;
use App\Models\Farmer\MedicalRecord;
use App\Models\Farmer\MilkProduction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AnalyticsController extends Controller
{
    public function farmerAnalysis(Request $request)
    {
        $context = $this->resolvePeriodContext($request, 'monthly');
        $filters = $this->normalizeFarmerFilters($request, $context['period']);
        $options = $this->farmerFilterOptions();

        $farmerIds = $this->filteredFarmerIds($filters);
        $farmerIdArray = $this->idsOrZero($farmerIds);

        $dairyIds = Dairy::query()
            ->whereIn('farmer_id', $farmerIdArray)
            ->when($filters['dairy_id'] > 0, fn ($query) => $query->where('id', $filters['dairy_id']))
            ->pluck('id');
        $dairyIdArray = $this->idsOrZero($dairyIds);

        $animalIds = Animal::query()
            ->whereIn('farmer_id', $farmerIdArray)
            ->when($filters['animal_id'] > 0, fn ($query) => $query->where('id', $filters['animal_id']))
            ->pluck('id');
        $animalIdArray = $this->idsOrZero($animalIds);

        $totalFarmers = $farmerIds->count();
        $activeFarmers = Schema::hasColumn('farmers', 'is_active')
            ? Farmer::query()->whereIn('id', $farmerIdArray)->where('is_active', true)->count()
            : $totalFarmers;
        $currentFarmerGrowth = Farmer::query()
            ->whereIn('id', $farmerIdArray)
            ->whereBetween('created_at', [$context['start'], $context['end']])
            ->count();
        $previousFarmerGrowth = Farmer::query()
            ->whereIn('id', $farmerIdArray)
            ->whereBetween('created_at', [$context['previous_start'], $context['previous_end']])
            ->count();
        $farmerGrowthRate = $this->percentChange((float) $currentFarmerGrowth, (float) $previousFarmerGrowth);

        $milkBaseCurrent = MilkProduction::query()
            ->whereIn('animal_id', $animalIdArray)
            ->when($dairyIds->isNotEmpty(), fn ($query) => $query->whereIn('dairy_id', $dairyIdArray));
        $this->applyDateRange($milkBaseCurrent, 'date', $context['start'], $context['end'], true);

        $milkBasePrevious = MilkProduction::query()
            ->whereIn('animal_id', $animalIdArray)
            ->when($dairyIds->isNotEmpty(), fn ($query) => $query->whereIn('dairy_id', $dairyIdArray));
        $this->applyDateRange($milkBasePrevious, 'date', $context['previous_start'], $context['previous_end'], true);

        $feedingBaseCurrent = FeedingRecord::query()
            ->whereIn('farmer_id', $farmerIdArray)
            ->when($animalIds->isNotEmpty(), fn ($query) => $query->whereIn('animal_id', $animalIdArray));
        $this->applyDateRange($feedingBaseCurrent, 'date', $context['start'], $context['end'], true);

        $pregnancyBaseCurrent = AnimalPregnancy::query()
            ->whereIn('farmer_id', $farmerIdArray)
            ->when($animalIds->isNotEmpty(), fn ($query) => $query->whereIn('animal_id', $animalIdArray));
        $this->applyDateRange($pregnancyBaseCurrent, 'created_at', $context['start'], $context['end']);

        $medicalBaseCurrent = MedicalRecord::query()
            ->whereIn('farmer_id', $farmerIdArray)
            ->when($animalIds->isNotEmpty(), fn ($query) => $query->whereIn('animal_id', $animalIdArray));
        $this->applyDateRange($medicalBaseCurrent, 'date', $context['start'], $context['end'], true);

        $mastitisBaseCurrent = MastitisRecord::query()
            ->whereIn('farmer_id', $farmerIdArray)
            ->when($animalIds->isNotEmpty(), fn ($query) => $query->whereIn('animal_id', $animalIdArray));
        $this->applyDateRange($mastitisBaseCurrent, 'date', $context['start'], $context['end'], true);

        $dmiBaseCurrent = DmiRecord::query()
            ->whereIn('farmer_id', $farmerIdArray)
            ->when($animalIds->isNotEmpty(), fn ($query) => $query->whereIn('animal_id', $animalIdArray));
        $this->applyDateRange($dmiBaseCurrent, 'date', $context['start'], $context['end'], true);

        $milkVolume = round((float) (clone $milkBaseCurrent)->sum('total_milk'), 2);
        $previousMilkVolume = round((float) (clone $milkBasePrevious)->sum('total_milk'), 2);
        $milkGrowthRate = $this->percentChange($milkVolume, $previousMilkVolume);

        $revenue = round((float) (clone $milkBaseCurrent)
            ->selectRaw('COALESCE(SUM(total_milk * COALESCE(rate, 0)), 0) as total')
            ->value('total'), 2);
        $previousRevenue = round((float) (clone $milkBasePrevious)
            ->selectRaw('COALESCE(SUM(total_milk * COALESCE(rate, 0)), 0) as total')
            ->value('total'), 2);
        $revenueGrowthRate = $this->percentChange($revenue, $previousRevenue);

        $feedingRecordsCount = (clone $feedingBaseCurrent)->count();
        $pregnancyRecordsCount = (clone $pregnancyBaseCurrent)->count();
        $healthRecordsCount = (clone $medicalBaseCurrent)->count()
            + (clone $mastitisBaseCurrent)->count()
            + (clone $dmiBaseCurrent)->count();
        $animalsCount = Animal::query()->whereIn('id', $animalIdArray)->count();
        $dairiesCount = Dairy::query()->whereIn('id', $dairyIdArray)->count();

        $currentActiveFarmerIds = Farmer::query()
            ->whereIn('id', $farmerIdArray)
            ->whereBetween('active_session_at', [$context['start'], $context['end']])
            ->pluck('id');
        $previousActiveFarmerIds = Farmer::query()
            ->whereIn('id', $farmerIdArray)
            ->whereBetween('active_session_at', [$context['previous_start'], $context['previous_end']])
            ->pluck('id');

        $activeUsers = $currentActiveFarmerIds->count();
        $retentionRate = $this->retentionRate($currentActiveFarmerIds, $previousActiveFarmerIds);
        $activeRate = $totalFarmers > 0 ? round(($activeUsers / $totalFarmers) * 100, 1) : 0.0;

        $farmerGrowthMap = $this->aggregateMap(
            Farmer::query()->whereIn('id', $farmerIdArray)->whereBetween('created_at', [$context['start'], $context['end']]),
            'created_at',
            $context['period'],
            'COUNT(*)',
            'total'
        );
        $milkGrowthMap = $this->aggregateMap(
            clone $milkBaseCurrent,
            'date',
            $context['period'],
            'COALESCE(SUM(total_milk), 0)',
            'total'
        );
        $revenueMap = $this->aggregateMap(
            clone $milkBaseCurrent,
            'date',
            $context['period'],
            'COALESCE(SUM(total_milk * COALESCE(rate, 0)), 0)',
            'total'
        );

        $areaColumn = $filters['district'] !== '' ? 'village' : ($filters['state'] !== '' ? 'district' : 'state');
        $areaLabel = $areaColumn === 'village' ? 'Village' : ($areaColumn === 'district' ? 'District' : 'State');
        $areaWiseRows = Farmer::query()
            ->whereIn('id', $farmerIdArray)
            ->selectRaw("COALESCE(NULLIF(TRIM({$areaColumn}), ''), 'Unknown') as area_name, COUNT(*) as total")
            ->groupBy('area_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->area_name,
                'total' => (int) $row->total,
            ])
            ->values();

        $villageActivityRows = $this->buildVillageActivityRows($farmerIdArray, $animalIdArray, $dairyIdArray, $context);
        $mostActiveVillage = $villageActivityRows->first()['label'] ?? 'No activity';
        $mostActiveVillageScore = $villageActivityRows->first()['total'] ?? 0;
        $topPerformingFarmers = $this->buildTopFarmerRows($farmerIdArray, $animalIdArray, $dairyIdArray, $context);

        $kpis = [
            ['label' => 'Total Farmers', 'value' => number_format($totalFarmers)],
            ['label' => 'Active Users', 'value' => number_format($activeUsers)],
            ['label' => 'Retention Rate', 'value' => $retentionRate.'%'],
            ['label' => 'Revenue', 'value' => 'Rs '.number_format($revenue, 2)],
            ['label' => 'Milk Volume', 'value' => number_format($milkVolume, 2).' L'],
            ['label' => 'Feeding Records', 'value' => number_format($feedingRecordsCount)],
            ['label' => 'Pregnancy Records', 'value' => number_format($pregnancyRecordsCount)],
            ['label' => 'Health Records', 'value' => number_format($healthRecordsCount)],
            ['label' => 'Most Active Village', 'value' => $mostActiveVillage],
        ];

        $filtersSummary = $this->humanizeFilters([
            'Period' => ucfirst($context['period']),
            'From Date' => $context['start']->toDateString(),
            'To Date' => $context['end']->toDateString(),
            'Farmer' => $this->optionLabel($options['farmers'], $filters['farmer_id']),
            'Dairy' => $this->optionLabel($options['dairies'], $filters['dairy_id']),
            'Animal' => $this->optionLabel($options['animals'], $filters['animal_id']),
            'Village' => $filters['village'],
            'District' => $filters['district'],
            'State' => $filters['state'],
            'Status' => ucfirst($filters['status']),
        ]);

        $exportTables = [
            [
                'title' => 'Top Performing Farmers',
                'columns' => ['Farmer', 'Village', 'Animals', 'Milk (L)', 'Revenue', 'Feedings', 'Last App Activity'],
                'rows' => $topPerformingFarmers->map(fn ($row) => [
                    $row['name'],
                    $row['village'],
                    $row['animals_count'],
                    number_format($row['milk_liters'], 2),
                    'Rs '.number_format($row['revenue'], 2),
                    $row['feeding_records'],
                    $row['last_activity'],
                ])->all(),
            ],
            [
                'title' => 'Area Wise Farmer Spread',
                'columns' => [$areaLabel, 'Farmers'],
                'rows' => $areaWiseRows->map(fn ($row) => [$row['label'], $row['total']])->all(),
            ],
            [
                'title' => 'Village Activity Score',
                'columns' => ['Village', 'Activity Score'],
                'rows' => $villageActivityRows->map(fn ($row) => [$row['label'], $row['total']])->all(),
            ],
        ];

        if ($response = $this->maybeExportReport($request, 'Farmer Report', $filtersSummary, $kpis, $exportTables)) {
            return $response;
        }

        return view('analytics.farmer_analysis', [
            'periodOptions' => $this->periodOptions(),
            'filters' => $filters,
            'context' => $context,
            'options' => $options,
            'totalFarmers' => $totalFarmers,
            'activeFarmers' => $activeFarmers,
            'activeUsers' => $activeUsers,
            'retentionRate' => $retentionRate,
            'activeRate' => $activeRate,
            'milkVolume' => $milkVolume,
            'revenue' => $revenue,
            'animalsCount' => $animalsCount,
            'dairiesCount' => $dairiesCount,
            'feedingRecordsCount' => $feedingRecordsCount,
            'pregnancyRecordsCount' => $pregnancyRecordsCount,
            'healthRecordsCount' => $healthRecordsCount,
            'currentFarmerGrowth' => $currentFarmerGrowth,
            'farmerGrowthRate' => $farmerGrowthRate,
            'milkGrowthRate' => $milkGrowthRate,
            'revenueGrowthRate' => $revenueGrowthRate,
            'mostActiveVillage' => $mostActiveVillage,
            'mostActiveVillageScore' => $mostActiveVillageScore,
            'bucketLabels' => $this->bucketLabels($context['buckets']),
            'farmerGrowthSeries' => $this->seriesFromBuckets($context['buckets'], $farmerGrowthMap),
            'milkGrowthSeries' => $this->seriesFromBuckets($context['buckets'], $milkGrowthMap, true),
            'revenueSeries' => $this->seriesFromBuckets($context['buckets'], $revenueMap, true),
            'areaWiseLabel' => $areaLabel,
            'areaWiseRows' => $areaWiseRows,
            'topVillageRows' => $villageActivityRows,
            'topPerformingFarmers' => $topPerformingFarmers,
        ]);
    }

    public function dairyAnalysis(Request $request)
    {
        $context = $this->resolvePeriodContext($request, 'monthly');
        $filters = $this->normalizeDairyFilters($request, $context['period']);
        $options = $this->dairyFilterOptions();

        $dairyQuery = Dairy::query()
            ->when($filters['dairy_id'] > 0, fn ($query) => $query->where('id', $filters['dairy_id']))
            ->when($filters['farmer_id'] > 0, fn ($query) => $query->where('farmer_id', $filters['farmer_id']))
            ->when($filters['district'] !== '', fn ($query) => $query->where('district', $filters['district']))
            ->when($filters['state'] !== '', fn ($query) => $query->where('state', $filters['state']))
            ->when($filters['status'] !== 'all', fn ($query) => $query->where('is_active', $filters['status'] === 'active'));

        $dairyIds = $dairyQuery->pluck('id');
        $dairyIdArray = $this->idsOrZero($dairyIds);
        $farmerIds = Dairy::query()->whereIn('id', $dairyIdArray)->pluck('farmer_id')->unique();
        $farmerIdArray = $this->idsOrZero($farmerIds);

        $milkBaseCurrent = MilkProduction::query()->whereIn('dairy_id', $dairyIdArray);
        $this->applyDateRange($milkBaseCurrent, 'date', $context['start'], $context['end'], true);

        $paymentBaseCurrent = DairyPaymentEntry::query()
            ->whereIn('dairy_id', $dairyIdArray)
            ->whereIn('farmer_id', $farmerIdArray);
        $this->applyDateRange($paymentBaseCurrent, 'payment_date', $context['start'], $context['end'], true);

        $paymentBasePrevious = DairyPaymentEntry::query()
            ->whereIn('dairy_id', $dairyIdArray)
            ->whereIn('farmer_id', $farmerIdArray);
        $this->applyDateRange($paymentBasePrevious, 'payment_date', $context['previous_start'], $context['previous_end'], true);

        $milkBasePrevious = MilkProduction::query()->whereIn('dairy_id', $dairyIdArray);
        $this->applyDateRange($milkBasePrevious, 'date', $context['previous_start'], $context['previous_end'], true);

        $totalDairies = $dairyIds->count();
        $activeDairies = Dairy::query()->whereIn('id', $dairyIdArray)->where('is_active', true)->count();
        $attachedFarmers = $farmerIds->count();
        $milkVolume = round((float) (clone $milkBaseCurrent)->sum('total_milk'), 2);
        $previousMilkVolume = round((float) (clone $milkBasePrevious)->sum('total_milk'), 2);
        $milkGrowthRate = $this->percentChange($milkVolume, $previousMilkVolume);

        $totalAmount = round((float) (clone $paymentBaseCurrent)->sum('total_amount'), 2);
        $paidAmount = round((float) (clone $paymentBaseCurrent)->sum('paid_amount'), 2);
        $pendingAmount = round((float) (clone $paymentBaseCurrent)->sum('closing_balance'), 2);
        $previousPaidAmount = round((float) (clone $paymentBasePrevious)->sum('paid_amount'), 2);
        $paymentGrowthRate = $this->percentChange($paidAmount, $previousPaidAmount);
        $collectionRate = $totalAmount > 0 ? round(($paidAmount / $totalAmount) * 100, 1) : 0.0;

        $dairyGrowthMap = $this->aggregateMap(
            Dairy::query()->whereIn('id', $dairyIdArray)->whereBetween('created_at', [$context['start'], $context['end']]),
            'created_at',
            $context['period'],
            'COUNT(*)',
            'total'
        );
        $milkMap = $this->aggregateMap(clone $milkBaseCurrent, 'date', $context['period'], 'COALESCE(SUM(total_milk), 0)', 'total');
        $paymentMap = $this->aggregateMap(clone $paymentBaseCurrent, 'payment_date', $context['period'], 'COALESCE(SUM(paid_amount), 0)', 'total');

        $dairyRows = Dairy::query()
            ->with('farmer')
            ->whereIn('id', $dairyIdArray)
            ->get()
            ->map(function (Dairy $dairy) use ($context) {
                $milkTotal = MilkProduction::query()
                    ->where('dairy_id', $dairy->id)
                    ->whereBetween('date', [$context['start']->toDateString(), $context['end']->toDateString()])
                    ->sum('total_milk');
                $paymentTotals = DairyPaymentEntry::query()
                    ->where('dairy_id', $dairy->id)
                    ->whereBetween('payment_date', [$context['start']->toDateString(), $context['end']->toDateString()])
                    ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount, COALESCE(SUM(paid_amount), 0) as paid_amount, COALESCE(SUM(closing_balance), 0) as pending_amount, MAX(payment_date) as latest_payment_date')
                    ->first();

                return [
                    'dairy_name' => $dairy->dairy_name ?: '-',
                    'farmer_name' => trim((string) optional($dairy->farmer)->first_name.' '.optional($dairy->farmer)->last_name) ?: '-',
                    'district' => $dairy->district ?: '-',
                    'state' => $dairy->state ?: '-',
                    'status' => $dairy->is_active ? 'Active' : 'Inactive',
                    'milk_total' => round((float) $milkTotal, 2),
                    'total_amount' => round((float) ($paymentTotals->total_amount ?? 0), 2),
                    'paid_amount' => round((float) ($paymentTotals->paid_amount ?? 0), 2),
                    'pending_amount' => round((float) ($paymentTotals->pending_amount ?? 0), 2),
                    'latest_payment_date' => ! empty($paymentTotals->latest_payment_date)
                        ? Carbon::parse($paymentTotals->latest_payment_date)->toDateString()
                        : '-',
                ];
            })
            ->sortByDesc('pending_amount')
            ->take(20)
            ->values();

        $topOutstanding = $dairyRows->sortByDesc('pending_amount')->take(10)->values();

        $kpis = [
            ['label' => 'Total Dairies', 'value' => number_format($totalDairies)],
            ['label' => 'Active Dairies', 'value' => number_format($activeDairies)],
            ['label' => 'Attached Farmers', 'value' => number_format($attachedFarmers)],
            ['label' => 'Milk Collection', 'value' => number_format($milkVolume, 2).' L'],
            ['label' => 'Payment Amount', 'value' => 'Rs '.number_format($paidAmount, 2)],
            ['label' => 'Pending Amount', 'value' => 'Rs '.number_format($pendingAmount, 2)],
            ['label' => 'Collection Rate', 'value' => $collectionRate.'%'],
        ];

        $filtersSummary = $this->humanizeFilters([
            'Period' => ucfirst($context['period']),
            'From Date' => $context['start']->toDateString(),
            'To Date' => $context['end']->toDateString(),
            'Dairy' => $this->optionLabel($options['dairies'], $filters['dairy_id']),
            'Farmer' => $this->optionLabel($options['farmers'], $filters['farmer_id']),
            'District' => $filters['district'],
            'State' => $filters['state'],
            'Status' => ucfirst($filters['status']),
        ]);

        $exportTables = [
            [
                'title' => 'Dairy Performance',
                'columns' => ['Dairy', 'Farmer', 'District', 'State', 'Status', 'Milk (L)', 'Total Amount', 'Paid', 'Pending', 'Latest Payment'],
                'rows' => $dairyRows->map(fn ($row) => [
                    $row['dairy_name'],
                    $row['farmer_name'],
                    $row['district'],
                    $row['state'],
                    $row['status'],
                    number_format($row['milk_total'], 2),
                    'Rs '.number_format($row['total_amount'], 2),
                    'Rs '.number_format($row['paid_amount'], 2),
                    'Rs '.number_format($row['pending_amount'], 2),
                    $row['latest_payment_date'],
                ])->all(),
            ],
        ];

        if ($response = $this->maybeExportReport($request, 'Dairy Report', $filtersSummary, $kpis, $exportTables)) {
            return $response;
        }

        return view('analytics.dairy_analysis', [
            'periodOptions' => $this->periodOptions(),
            'filters' => $filters,
            'context' => $context,
            'options' => $options,
            'totalDairies' => $totalDairies,
            'activeDairies' => $activeDairies,
            'attachedFarmers' => $attachedFarmers,
            'milkVolume' => $milkVolume,
            'milkGrowthRate' => $milkGrowthRate,
            'totalAmount' => $totalAmount,
            'paidAmount' => $paidAmount,
            'pendingAmount' => $pendingAmount,
            'paymentGrowthRate' => $paymentGrowthRate,
            'collectionRate' => $collectionRate,
            'bucketLabels' => $this->bucketLabels($context['buckets']),
            'dairyGrowthSeries' => $this->seriesFromBuckets($context['buckets'], $dairyGrowthMap),
            'milkSeries' => $this->seriesFromBuckets($context['buckets'], $milkMap, true),
            'paymentSeries' => $this->seriesFromBuckets($context['buckets'], $paymentMap, true),
            'dairyRows' => $dairyRows,
            'topOutstanding' => $topOutstanding,
        ]);
    }

    public function doctorAnalysis(Request $request)
    {
        $context = $this->resolvePeriodContext($request, 'monthly');
        $filters = $this->normalizeDoctorFilters($request, $context['period']);
        $options = $this->doctorFilterOptions();

        $doctorQuery = Doctor::query()
            ->when($filters['doctor_id'] > 0, fn ($query) => $query->where('id', $filters['doctor_id']))
            ->when($filters['status'] !== 'all', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['district'] !== '', fn ($query) => $query->where('district', $filters['district']))
            ->when($filters['state'] !== '', fn ($query) => $query->where('state', $filters['state']));

        $doctorIds = $doctorQuery->pluck('id');
        $doctorIdArray = $this->idsOrZero($doctorIds);

        $appointmentBaseCurrent = DoctorAppointment::query()->whereIn('doctor_id', $doctorIdArray);
        $this->applyDateRange($appointmentBaseCurrent, 'created_at', $context['start'], $context['end']);

        $appointmentBasePrevious = DoctorAppointment::query()->whereIn('doctor_id', $doctorIdArray);
        $this->applyDateRange($appointmentBasePrevious, 'created_at', $context['previous_start'], $context['previous_end']);

        $totalDoctors = $doctorIds->count();
        $approvedDoctors = Doctor::query()->whereIn('id', $doctorIdArray)->where('status', 'approved')->count();
        $pendingDoctors = Doctor::query()->whereIn('id', $doctorIdArray)->where('status', 'pending')->count();
        $rejectedDoctors = Doctor::query()->whereIn('id', $doctorIdArray)->whereIn('status', ['rejected', 'declined'])->count();
        $newDoctors = Doctor::query()
            ->whereIn('id', $doctorIdArray)
            ->whereBetween('created_at', [$context['start'], $context['end']])
            ->count();
        $approvalRate = $totalDoctors > 0 ? round(($approvedDoctors / $totalDoctors) * 100, 1) : 0.0;

        $currentActiveDoctorIds = Doctor::query()
            ->whereIn('id', $doctorIdArray)
            ->whereBetween('last_live_location_at', [$context['start'], $context['end']])
            ->pluck('id')
            ->merge((clone $appointmentBaseCurrent)->pluck('doctor_id'))
            ->filter()
            ->unique()
            ->values();
        $previousActiveDoctorIds = Doctor::query()
            ->whereIn('id', $doctorIdArray)
            ->whereBetween('last_live_location_at', [$context['previous_start'], $context['previous_end']])
            ->pluck('id')
            ->merge((clone $appointmentBasePrevious)->pluck('doctor_id'))
            ->filter()
            ->unique()
            ->values();

        $activeDoctors = $currentActiveDoctorIds->count();
        $retentionRate = $this->retentionRate($currentActiveDoctorIds, $previousActiveDoctorIds);
        $totalAppointments = (clone $appointmentBaseCurrent)->count();
        $completedAppointments = (clone $appointmentBaseCurrent)->where('status', 'completed')->count();
        $completionRate = $totalAppointments > 0 ? round(($completedAppointments / $totalAppointments) * 100, 1) : 0.0;
        $onSiteTreatments = (clone $appointmentBaseCurrent)->where('onsite_treatment', true)->count();
        $followups = (clone $appointmentBaseCurrent)->where('followup_required', true)->count();
        $doctorRevenue = round((float) (clone $appointmentBaseCurrent)
            ->selectRaw('COALESCE(SUM(COALESCE(charges, 0) + COALESCE(fees, 0) + COALESCE(on_site_medicine_charges, 0)), 0) as total')
            ->value('total'), 2);
        $previousRevenue = round((float) (clone $appointmentBasePrevious)
            ->selectRaw('COALESCE(SUM(COALESCE(charges, 0) + COALESCE(fees, 0) + COALESCE(on_site_medicine_charges, 0)), 0) as total')
            ->value('total'), 2);
        $revenueGrowthRate = $this->percentChange($doctorRevenue, $previousRevenue);

        $doctorGrowthMap = $this->aggregateMap(
            Doctor::query()->whereIn('id', $doctorIdArray)->whereBetween('created_at', [$context['start'], $context['end']]),
            'created_at',
            $context['period'],
            'COUNT(*)',
            'total'
        );
        $appointmentGrowthMap = $this->aggregateMap(clone $appointmentBaseCurrent, 'created_at', $context['period'], 'COUNT(*)', 'total');
        $revenueMap = $this->aggregateMap(
            clone $appointmentBaseCurrent,
            'created_at',
            $context['period'],
            'COALESCE(SUM(COALESCE(charges, 0) + COALESCE(fees, 0) + COALESCE(on_site_medicine_charges, 0)), 0)',
            'total'
        );

        $statusLabels = ['Approved', 'Pending', 'Rejected', 'Other'];
        $statusSeries = [
            $approvedDoctors,
            $pendingDoctors,
            $rejectedDoctors,
            max(0, $totalDoctors - ($approvedDoctors + $pendingDoctors + $rejectedDoctors)),
        ];

        $doctorRows = Doctor::query()
            ->whereIn('id', $doctorIdArray)
            ->get()
            ->map(function (Doctor $doctor) use ($context) {
                $appointments = DoctorAppointment::query()
                    ->where('doctor_id', $doctor->id)
                    ->whereBetween('created_at', [$context['start'], $context['end']]);
                $appointmentCount = (clone $appointments)->count();
                $completedCount = (clone $appointments)->where('status', 'completed')->count();
                $revenue = round((float) (clone $appointments)
                    ->selectRaw('COALESCE(SUM(COALESCE(charges, 0) + COALESCE(fees, 0) + COALESCE(on_site_medicine_charges, 0)), 0) as total')
                    ->value('total'), 2);

                return [
                    'name' => $doctor->full_name ?: 'Doctor #'.$doctor->id,
                    'city' => $doctor->city ?: '-',
                    'district' => $doctor->district ?: '-',
                    'state' => $doctor->state ?: '-',
                    'status' => strtolower((string) ($doctor->status ?: 'pending')),
                    'appointments' => $appointmentCount,
                    'completed' => $completedCount,
                    'completion_rate' => $appointmentCount > 0 ? round(($completedCount / $appointmentCount) * 100, 1) : 0.0,
                    'onsite_treatments' => (clone $appointments)->where('onsite_treatment', true)->count(),
                    'revenue' => $revenue,
                    'last_live' => optional($doctor->last_live_location_at)->format('Y-m-d h:i A') ?: '-',
                ];
            })
            ->sortByDesc('revenue')
            ->take(20)
            ->values();

        $kpis = [
            ['label' => 'Total Doctors', 'value' => number_format($totalDoctors)],
            ['label' => 'Active Doctors', 'value' => number_format($activeDoctors)],
            ['label' => 'Approval Rate', 'value' => $approvalRate.'%'],
            ['label' => 'Appointments', 'value' => number_format($totalAppointments)],
            ['label' => 'Completed Appointments', 'value' => number_format($completedAppointments)],
            ['label' => 'Retention Rate', 'value' => $retentionRate.'%'],
            ['label' => 'Revenue', 'value' => 'Rs '.number_format($doctorRevenue, 2)],
            ['label' => 'On-site Treatments', 'value' => number_format($onSiteTreatments)],
            ['label' => 'Follow-ups', 'value' => number_format($followups)],
        ];

        $filtersSummary = $this->humanizeFilters([
            'Period' => ucfirst($context['period']),
            'From Date' => $context['start']->toDateString(),
            'To Date' => $context['end']->toDateString(),
            'Doctor' => $this->optionLabel($options['doctors'], $filters['doctor_id']),
            'District' => $filters['district'],
            'State' => $filters['state'],
            'Status' => ucfirst($filters['status']),
        ]);

        $exportTables = [
            [
                'title' => 'Doctor Performance',
                'columns' => ['Doctor', 'City', 'District', 'State', 'Status', 'Appointments', 'Completed', 'Completion Rate', 'On-site Treatments', 'Revenue', 'Last Live'],
                'rows' => $doctorRows->map(fn ($row) => [
                    $row['name'],
                    $row['city'],
                    $row['district'],
                    $row['state'],
                    ucfirst($row['status']),
                    $row['appointments'],
                    $row['completed'],
                    $row['completion_rate'].'%',
                    $row['onsite_treatments'],
                    'Rs '.number_format($row['revenue'], 2),
                    $row['last_live'],
                ])->all(),
            ],
        ];

        if ($response = $this->maybeExportReport($request, 'Doctor Report', $filtersSummary, $kpis, $exportTables)) {
            return $response;
        }

        return view('analytics.doctor_analysis', [
            'periodOptions' => $this->periodOptions(),
            'filters' => $filters,
            'context' => $context,
            'options' => $options,
            'totalDoctors' => $totalDoctors,
            'activeDoctors' => $activeDoctors,
            'approvedDoctors' => $approvedDoctors,
            'pendingDoctors' => $pendingDoctors,
            'rejectedDoctors' => $rejectedDoctors,
            'newDoctors' => $newDoctors,
            'approvalRate' => $approvalRate,
            'retentionRate' => $retentionRate,
            'totalAppointments' => $totalAppointments,
            'completedAppointments' => $completedAppointments,
            'completionRate' => $completionRate,
            'onSiteTreatments' => $onSiteTreatments,
            'followups' => $followups,
            'doctorRevenue' => $doctorRevenue,
            'revenueGrowthRate' => $revenueGrowthRate,
            'bucketLabels' => $this->bucketLabels($context['buckets']),
            'doctorGrowthSeries' => $this->seriesFromBuckets($context['buckets'], $doctorGrowthMap),
            'appointmentGrowthSeries' => $this->seriesFromBuckets($context['buckets'], $appointmentGrowthMap),
            'revenueSeries' => $this->seriesFromBuckets($context['buckets'], $revenueMap, true),
            'statusLabels' => $statusLabels,
            'statusSeries' => $statusSeries,
            'doctorRows' => $doctorRows,
        ]);
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
            ->selectRaw("doctor_appointments.doctor_id as doctor_id, COALESCE(SUM(COALESCE(doctor_appointments.charges, 0)), 0) as total, MAX(doctors.first_name) as first_name, MAX(doctors.last_name) as last_name")
            ->whereNotNull('doctor_appointments.charges')
            ->whereNotIn('doctor_appointments.status', ['cancelled', 'declined', 'rejected'])
            ->groupBy('doctor_appointments.doctor_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'label' => trim(((string) ($row->first_name ?? '')).' '.((string) ($row->last_name ?? ''))) !== ''
                    ? trim(((string) ($row->first_name ?? '')).' '.((string) ($row->last_name ?? '')))
                    : 'Unknown Doctor',
                'total' => (float) $row->total,
            ]);

        $topDairyEarnings = MilkProduction::query()
            ->leftJoin('dairies', 'dairies.id', '=', 'milk_productions.dairy_id')
            ->selectRaw("milk_productions.dairy_id as dairy_id, COALESCE(SUM(milk_productions.total_milk * COALESCE(milk_productions.rate, 0)), 0) as total, MAX(dairies.dairy_name) as dairy_name")
            ->groupBy('milk_productions.dairy_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'label' => trim((string) ($row->dairy_name ?? '')) !== ''
                    ? trim((string) $row->dairy_name)
                    : 'Unknown Dairy',
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

    private function normalizeFarmerFilters(Request $request, string $defaultPeriod): array
    {
        return [
            'period' => $this->sanitizePeriod($request->input('period', $defaultPeriod)),
            'farmer_id' => max((int) $request->integer('farmer_id'), 0),
            'dairy_id' => max((int) $request->integer('dairy_id'), 0),
            'animal_id' => max((int) $request->integer('animal_id'), 0),
            'village' => trim((string) $request->input('village', '')),
            'district' => trim((string) $request->input('district', '')),
            'state' => trim((string) $request->input('state', '')),
            'status' => in_array($request->input('status'), ['all', 'active', 'inactive'], true) ? $request->input('status') : 'all',
        ];
    }

    private function normalizeDairyFilters(Request $request, string $defaultPeriod): array
    {
        return [
            'period' => $this->sanitizePeriod($request->input('period', $defaultPeriod)),
            'dairy_id' => max((int) $request->integer('dairy_id'), 0),
            'farmer_id' => max((int) $request->integer('farmer_id'), 0),
            'district' => trim((string) $request->input('district', '')),
            'state' => trim((string) $request->input('state', '')),
            'status' => in_array($request->input('status'), ['all', 'active', 'inactive'], true) ? $request->input('status') : 'all',
        ];
    }

    private function normalizeDoctorFilters(Request $request, string $defaultPeriod): array
    {
        return [
            'period' => $this->sanitizePeriod($request->input('period', $defaultPeriod)),
            'doctor_id' => max((int) $request->integer('doctor_id'), 0),
            'district' => trim((string) $request->input('district', '')),
            'state' => trim((string) $request->input('state', '')),
            'status' => in_array($request->input('status'), ['all', 'approved', 'pending', 'rejected', 'declined'], true) ? $request->input('status') : 'all',
        ];
    }

    private function farmerFilterOptions(): array
    {
        return [
            'farmers' => Farmer::query()->orderBy('first_name')->get(['id', 'first_name', 'last_name'])->map(fn (Farmer $farmer) => [
                'id' => $farmer->id,
                'label' => trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) ?: 'Farmer #'.$farmer->id,
            ])->values(),
            'dairies' => Dairy::query()->orderBy('dairy_name')->get(['id', 'dairy_name'])->map(fn (Dairy $dairy) => [
                'id' => $dairy->id,
                'label' => $dairy->dairy_name ?: 'Dairy #'.$dairy->id,
            ])->values(),
            'animals' => Animal::query()->orderBy('animal_name')->get(['id', 'animal_name', 'tag_number'])->map(fn (Animal $animal) => [
                'id' => $animal->id,
                'label' => trim(($animal->animal_name ?? '').' '.($animal->tag_number ? '('.$animal->tag_number.')' : '')) ?: 'Animal #'.$animal->id,
            ])->values(),
            'villages' => Farmer::query()->selectRaw("COALESCE(NULLIF(TRIM(village), ''), 'Unknown') as label")->groupBy('label')->orderBy('label')->pluck('label')->values(),
            'districts' => Farmer::query()->selectRaw("COALESCE(NULLIF(TRIM(district), ''), 'Unknown') as label")->groupBy('label')->orderBy('label')->pluck('label')->values(),
            'states' => Farmer::query()->selectRaw("COALESCE(NULLIF(TRIM(state), ''), 'Unknown') as label")->groupBy('label')->orderBy('label')->pluck('label')->values(),
        ];
    }

    private function dairyFilterOptions(): array
    {
        return [
            'dairies' => Dairy::query()->orderBy('dairy_name')->get(['id', 'dairy_name'])->map(fn (Dairy $dairy) => [
                'id' => $dairy->id,
                'label' => $dairy->dairy_name ?: 'Dairy #'.$dairy->id,
            ])->values(),
            'farmers' => Farmer::query()->orderBy('first_name')->get(['id', 'first_name', 'last_name'])->map(fn (Farmer $farmer) => [
                'id' => $farmer->id,
                'label' => trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) ?: 'Farmer #'.$farmer->id,
            ])->values(),
            'districts' => Dairy::query()->selectRaw("COALESCE(NULLIF(TRIM(district), ''), 'Unknown') as label")->groupBy('label')->orderBy('label')->pluck('label')->values(),
            'states' => Dairy::query()->selectRaw("COALESCE(NULLIF(TRIM(state), ''), 'Unknown') as label")->groupBy('label')->orderBy('label')->pluck('label')->values(),
        ];
    }

    private function doctorFilterOptions(): array
    {
        return [
            'doctors' => Doctor::query()->orderBy('first_name')->get(['id', 'first_name', 'last_name'])->map(fn (Doctor $doctor) => [
                'id' => $doctor->id,
                'label' => $doctor->full_name ?: 'Doctor #'.$doctor->id,
            ])->values(),
            'districts' => Doctor::query()->selectRaw("COALESCE(NULLIF(TRIM(district), ''), 'Unknown') as label")->groupBy('label')->orderBy('label')->pluck('label')->values(),
            'states' => Doctor::query()->selectRaw("COALESCE(NULLIF(TRIM(state), ''), 'Unknown') as label")->groupBy('label')->orderBy('label')->pluck('label')->values(),
        ];
    }

    private function filteredFarmerIds(array $filters): Collection
    {
        $query = Farmer::query()
            ->when($filters['farmer_id'] > 0, fn ($builder) => $builder->where('id', $filters['farmer_id']))
            ->when($filters['village'] !== '', fn ($builder) => $builder->where('village', $filters['village']))
            ->when($filters['district'] !== '', fn ($builder) => $builder->where('district', $filters['district']))
            ->when($filters['state'] !== '', fn ($builder) => $builder->where('state', $filters['state']))
            ->when($filters['status'] !== 'all' && Schema::hasColumn('farmers', 'is_active'), fn ($builder) => $builder->where('is_active', $filters['status'] === 'active'));

        if ($filters['dairy_id'] > 0) {
            $query->whereIn('id', Dairy::query()->where('id', $filters['dairy_id'])->pluck('farmer_id'));
        }
        if ($filters['animal_id'] > 0) {
            $query->whereIn('id', Animal::query()->where('id', $filters['animal_id'])->pluck('farmer_id'));
        }

        return $query->pluck('id');
    }

    private function buildVillageActivityRows(array $farmerIds, array $animalIds, array $dairyIds, array $context): Collection
    {
        $activity = collect();
        $addRows = function (Collection $rows) use (&$activity) {
            foreach ($rows as $row) {
                $label = trim((string) ($row->label ?? '')) !== '' ? trim((string) $row->label) : 'Unknown';
                $activity[$label] = (int) ($activity[$label] ?? 0) + (int) ($row->total ?? 0);
            }
        };

        $milkRows = MilkProduction::query()
            ->join('animals', 'animals.id', '=', 'milk_productions.animal_id')
            ->join('farmers', 'farmers.id', '=', 'animals.farmer_id')
            ->whereIn('animals.id', $animalIds)
            ->when(! empty(array_filter($dairyIds, fn ($id) => $id > 0)), fn ($query) => $query->whereIn('milk_productions.dairy_id', $dairyIds))
            ->whereBetween('milk_productions.date', [$context['start']->toDateString(), $context['end']->toDateString()])
            ->selectRaw("COALESCE(NULLIF(TRIM(farmers.village), ''), 'Unknown') as label, COUNT(*) as total")
            ->groupBy('label')
            ->get();
        $addRows($milkRows);

        $feedingRows = FeedingRecord::query()
            ->join('farmers', 'farmers.id', '=', 'feeding_records.farmer_id')
            ->whereIn('feeding_records.farmer_id', $farmerIds)
            ->whereIn('feeding_records.animal_id', $animalIds)
            ->whereBetween('feeding_records.date', [$context['start']->toDateString(), $context['end']->toDateString()])
            ->selectRaw("COALESCE(NULLIF(TRIM(farmers.village), ''), 'Unknown') as label, COUNT(*) as total")
            ->groupBy('label')
            ->get();
        $addRows($feedingRows);

        $medicalRows = MedicalRecord::query()
            ->join('farmers', 'farmers.id', '=', 'medical_records.farmer_id')
            ->whereIn('medical_records.farmer_id', $farmerIds)
            ->whereBetween('medical_records.date', [$context['start']->toDateString(), $context['end']->toDateString()])
            ->selectRaw("COALESCE(NULLIF(TRIM(farmers.village), ''), 'Unknown') as label, COUNT(*) as total")
            ->groupBy('label')
            ->get();
        $addRows($medicalRows);

        $mastitisRows = MastitisRecord::query()
            ->join('farmers', 'farmers.id', '=', 'mastitis_records.farmer_id')
            ->whereIn('mastitis_records.farmer_id', $farmerIds)
            ->whereBetween('mastitis_records.date', [$context['start']->toDateString(), $context['end']->toDateString()])
            ->selectRaw("COALESCE(NULLIF(TRIM(farmers.village), ''), 'Unknown') as label, COUNT(*) as total")
            ->groupBy('label')
            ->get();
        $addRows($mastitisRows);

        $pregnancyRows = AnimalPregnancy::query()
            ->join('farmers', 'farmers.id', '=', 'animal_pregnancies.farmer_id')
            ->whereIn('animal_pregnancies.farmer_id', $farmerIds)
            ->whereBetween('animal_pregnancies.created_at', [$context['start'], $context['end']])
            ->selectRaw("COALESCE(NULLIF(TRIM(farmers.village), ''), 'Unknown') as label, COUNT(*) as total")
            ->groupBy('label')
            ->get();
        $addRows($pregnancyRows);

        return $activity
            ->map(fn ($total, $label) => ['label' => $label, 'total' => (int) $total])
            ->sortByDesc('total')
            ->values()
            ->take(10);
    }

    private function buildTopFarmerRows(array $farmerIds, array $animalIds, array $dairyIds, array $context): Collection
    {
        $milkRows = MilkProduction::query()
            ->join('animals', 'animals.id', '=', 'milk_productions.animal_id')
            ->join('farmers', 'farmers.id', '=', 'animals.farmer_id')
            ->whereIn('animals.id', $animalIds)
            ->when(! empty(array_filter($dairyIds, fn ($id) => $id > 0)), fn ($query) => $query->whereIn('milk_productions.dairy_id', $dairyIds))
            ->whereBetween('milk_productions.date', [$context['start']->toDateString(), $context['end']->toDateString()])
            ->selectRaw("farmers.id as farmer_id, MAX(CONCAT_WS(' ', farmers.first_name, farmers.last_name)) as farmer_name, MAX(COALESCE(NULLIF(TRIM(farmers.village), ''), 'Unknown')) as village_name, COUNT(DISTINCT animals.id) as animals_count, COALESCE(SUM(milk_productions.total_milk), 0) as milk_liters, COALESCE(SUM(milk_productions.total_milk * COALESCE(milk_productions.rate, 0)), 0) as revenue")
            ->groupBy('farmers.id')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->keyBy('farmer_id');

        $feedingMap = FeedingRecord::query()
            ->whereIn('farmer_id', $farmerIds)
            ->whereIn('animal_id', $animalIds)
            ->whereBetween('date', [$context['start']->toDateString(), $context['end']->toDateString()])
            ->selectRaw('farmer_id, COUNT(*) as total')
            ->groupBy('farmer_id')
            ->pluck('total', 'farmer_id');

        return $milkRows->map(function ($row) use ($feedingMap) {
            $farmer = Farmer::find($row->farmer_id);
            return [
                'name' => trim((string) ($row->farmer_name ?? '')) !== '' ? trim((string) $row->farmer_name) : 'Farmer #'.$row->farmer_id,
                'village' => $row->village_name,
                'animals_count' => (int) $row->animals_count,
                'milk_liters' => round((float) $row->milk_liters, 2),
                'revenue' => round((float) $row->revenue, 2),
                'feeding_records' => (int) ($feedingMap[$row->farmer_id] ?? 0),
                'last_activity' => optional($farmer?->active_session_at)->format('Y-m-d h:i A') ?: '-',
            ];
        })->values();
    }

    private function maybeExportReport(Request $request, string $title, array $filtersSummary, array $kpis, array $tables)
    {
        $export = strtolower((string) $request->query('export', ''));
        if (! in_array($export, ['excel', 'pdf'], true)) {
            return null;
        }

        $html = view('analytics.report_export', [
            'reportTitle' => $title,
            'generatedAt' => now()->format('d M Y h:i A'),
            'filtersSummary' => $filtersSummary,
            'kpis' => $kpis,
            'tables' => $tables,
            'isPdf' => $export === 'pdf',
        ])->render();

        if ($export === 'excel') {
            $filename = strtolower(str_replace(' ', '-', $title)).'-'.now()->format('Y-m-d').'.xls';
            return response($html, 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        return response($html);
    }

    private function humanizeFilters(array $filters): array
    {
        return collect($filters)
            ->filter(fn ($value) => trim((string) $value) !== '' && trim((string) $value) !== '-')
            ->map(fn ($value, $label) => ['label' => $label, 'value' => (string) $value])
            ->values()
            ->all();
    }

    private function optionLabel(Collection $options, int $selectedId): string
    {
        if ($selectedId <= 0) {
            return '-';
        }

        $match = $options->firstWhere('id', $selectedId);

        return is_array($match) ? (string) ($match['label'] ?? '-') : '-';
    }

    private function resolvePeriodContext(Request $request, string $defaultPeriod = 'monthly'): array
    {
        $period = $this->sanitizePeriod($request->input('period', $defaultPeriod));
        $now = now();

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $start = Carbon::parse((string) $request->input('from_date'))->startOfDay();
            $end = Carbon::parse((string) $request->input('to_date'))->endOfDay();
            if ($end->lt($start)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }
        } else {
            [$start, $end] = match ($period) {
                'daily' => [$now->copy()->subDays(13)->startOfDay(), $now->copy()->endOfDay()],
                'weekly' => [$now->copy()->subWeeks(11)->startOfWeek(), $now->copy()->endOfWeek()],
                'yearly' => [$now->copy()->subYears(4)->startOfYear(), $now->copy()->endOfYear()],
                default => [$now->copy()->subMonths(11)->startOfMonth(), $now->copy()->endOfMonth()],
            };
        }

        [$previousStart, $previousEnd] = match ($period) {
            'daily' => [
                $start->copy()->subDays($start->diffInDays($end) + 1)->startOfDay(),
                $start->copy()->subDay()->endOfDay(),
            ],
            'weekly' => [
                $start->copy()->subWeeks($start->copy()->startOfWeek()->diffInWeeks($end->copy()->endOfWeek()) + 1)->startOfWeek(),
                $start->copy()->subWeek()->endOfWeek(),
            ],
            'yearly' => [
                $start->copy()->subYears($start->copy()->startOfYear()->diffInYears($end->copy()->endOfYear()) + 1)->startOfYear(),
                $start->copy()->subYear()->endOfYear(),
            ],
            default => [
                $start->copy()->subMonths($start->copy()->startOfMonth()->diffInMonths($end->copy()->endOfMonth()) + 1)->startOfMonth(),
                $start->copy()->subMonth()->endOfMonth(),
            ],
        };

        return [
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
            'buckets' => $this->buildBuckets($start, $end, $period),
        ];
    }

    private function sanitizePeriod(string $period): string
    {
        return in_array($period, ['daily', 'weekly', 'monthly', 'yearly'], true) ? $period : 'monthly';
    }

    private function buildBuckets(Carbon $start, Carbon $end, string $period): Collection
    {
        $buckets = collect();
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            if ($period === 'daily') {
                $bucketStart = $cursor->copy()->startOfDay();
                $key = $bucketStart->format('Y-m-d');
                $label = $bucketStart->format('d M');
                $cursor->addDay();
            } elseif ($period === 'weekly') {
                $bucketStart = $cursor->copy()->startOfWeek();
                $key = $bucketStart->format('o').'-W'.$bucketStart->format('W');
                $label = 'W'.$bucketStart->format('W').' '.$bucketStart->format('Y');
                $cursor->addWeek();
            } elseif ($period === 'yearly') {
                $bucketStart = $cursor->copy()->startOfYear();
                $key = $bucketStart->format('Y');
                $label = $bucketStart->format('Y');
                $cursor->addYear();
            } else {
                $bucketStart = $cursor->copy()->startOfMonth();
                $key = $bucketStart->format('Y-m');
                $label = $bucketStart->format('M Y');
                $cursor->addMonth();
            }

            $buckets->push([
                'key' => $key,
                'label' => $label,
            ]);
        }

        return $buckets;
    }

    private function bucketLabels(Collection $buckets): array
    {
        return $buckets->pluck('label')->values()->all();
    }

    private function aggregateMap($query, string $dateColumn, string $period, string $aggregateExpression, string $alias): Collection
    {
        $bucketExpression = $this->periodSqlExpression($dateColumn, $period);

        return $query->selectRaw("{$bucketExpression} as bucket_key, {$aggregateExpression} as {$alias}")
            ->groupBy('bucket_key')
            ->pluck($alias, 'bucket_key');
    }

    private function periodSqlExpression(string $column, string $period): string
    {
        return match ($period) {
            'daily' => "DATE_FORMAT({$column}, '%Y-%m-%d')",
            'weekly' => "CONCAT(DATE_FORMAT({$column}, '%x'), '-W', DATE_FORMAT({$column}, '%v'))",
            'yearly' => "DATE_FORMAT({$column}, '%Y')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    private function seriesFromBuckets(Collection $buckets, Collection $map, bool $float = false): array
    {
        return $buckets->map(function ($bucket) use ($map, $float) {
            $value = $map[$bucket['key']] ?? 0;
            return $float ? round((float) $value, 2) : (int) $value;
        })->values()->all();
    }

    private function applyDateRange($query, string $column, Carbon $start, Carbon $end, bool $isDate = false): void
    {
        $query->whereBetween($column, [$isDate ? $start->toDateString() : $start, $isDate ? $end->toDateString() : $end]);
    }

    private function idsOrZero(Collection $ids): array
    {
        $values = $ids->filter(fn ($id) => (int) $id > 0)->values()->all();
        return ! empty($values) ? $values : [0];
    }

    private function retentionRate(Collection $currentIds, Collection $previousIds): float
    {
        $previousCount = $previousIds->count();
        if ($previousCount === 0) {
            return 0.0;
        }

        return round($currentIds->intersect($previousIds)->count() / $previousCount * 100, 1);
    }

    private function periodOptions(): array
    {
        return [
            ['value' => 'daily', 'label' => 'Daily'],
            ['value' => 'weekly', 'label' => 'Weekly'],
            ['value' => 'monthly', 'label' => 'Monthly'],
            ['value' => 'yearly', 'label' => 'Yearly'],
        ];
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

    private function percentChange(float $current, float $previous): float
    {
        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
