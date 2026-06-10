@extends('layouts.app')
@section('title', 'Farmer Report')

@php
    $pdfExportUrl = request()->fullUrlWithQuery(['export' => 'pdf']);
    $excelExportUrl = request()->fullUrlWithQuery(['export' => 'excel']);
@endphp

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box d-md-flex justify-content-md-between align-items-center">
            <div>
                <h4 class="page-title mb-1">Farmer Report</h4>
                <p class="text-muted mb-0">Growth, milk, revenue, retention, area performance, and activity reports in one screen.</p>
            </div>
            <div>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Corzin</a></li>
                    <li class="breadcrumb-item"><a href="#">Report</a></li>
                    <li class="breadcrumb-item active">Farmer Report</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6 col-lg-3 col-xl">
        <div class="card h-100 border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #0f766e, #14b8a6);">
            <div class="card-body">
                <small class="text-uppercase fw-semibold" style="color: rgba(255,255,255,.8);">Total Farmers</small>
                <h3 class="mt-2 mb-1">{{ number_format($totalFarmers) }}</h3>
                <span style="color: rgba(255,255,255,.82);">Filtered farmer base</span>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 col-xl">
        <div class="card h-100 border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #1d4ed8, #60a5fa);">
            <div class="card-body">
                <small class="text-uppercase fw-semibold" style="color: rgba(255,255,255,.8);">Active Users</small>
                <h3 class="mt-2 mb-1">{{ number_format($activeUsers) }}</h3>
                <span style="color: rgba(255,255,255,.82);">{{ $activeRate }}% of farmers active</span>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 col-xl">
        <div class="card h-100 border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #7c3aed, #a78bfa);">
            <div class="card-body">
                <small class="text-uppercase fw-semibold" style="color: rgba(255,255,255,.8);">Retention Rate</small>
                <h3 class="mt-2 mb-1">{{ $retentionRate }}%</h3>
                <span style="color: rgba(255,255,255,.82);">Compared with previous period</span>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 col-xl">
        <div class="card h-100 border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #b45309, #f59e0b);">
            <div class="card-body">
                <small class="text-uppercase fw-semibold" style="color: rgba(255,255,255,.8);">Revenue</small>
                <h3 class="mt-2 mb-1">Rs {{ number_format($revenue, 2) }}</h3>
                <span style="color: rgba(255,255,255,.82);">{{ $revenueGrowthRate >= 0 ? '+' : '' }}{{ $revenueGrowthRate }}% vs previous</span>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 col-xl">
        <div class="card h-100 border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #be123c, #fb7185);">
            <div class="card-body">
                <small class="text-uppercase fw-semibold" style="color: rgba(255,255,255,.8);">Milk Growth</small>
                <h3 class="mt-2 mb-1">{{ number_format($milkVolume, 2) }} L</h3>
                <span style="color: rgba(255,255,255,.82);">{{ $milkGrowthRate >= 0 ? '+' : '' }}{{ $milkGrowthRate }}% vs previous</span>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 col-xl">
        <div class="card h-100 border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #4338ca, #818cf8);">
            <div class="card-body">
                <small class="text-uppercase fw-semibold" style="color: rgba(255,255,255,.8);">Most Active Village</small>
                <h5 class="mt-2 mb-1">{{ $mostActiveVillage }}</h5>
                <span style="color: rgba(255,255,255,.82);">Activity score {{ $mostActiveVillageScore }}</span>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('analytics.farmer') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Period</label>
                    <select name="period" class="form-select">
                        @foreach($periodOptions as $periodOption)
                            <option value="{{ $periodOption['value'] }}" {{ $filters['period'] === $periodOption['value'] ? 'selected' : '' }}>{{ $periodOption['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Farmer</label>
                    <select name="farmer_id" class="form-select">
                        <option value="0">All Farmers</option>
                        @foreach($options['farmers'] as $farmer)
                            <option value="{{ $farmer['id'] }}" {{ $filters['farmer_id'] === $farmer['id'] ? 'selected' : '' }}>{{ $farmer['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Dairy</label>
                    <select name="dairy_id" class="form-select">
                        <option value="0">All Dairies</option>
                        @foreach($options['dairies'] as $dairy)
                            <option value="{{ $dairy['id'] }}" {{ $filters['dairy_id'] === $dairy['id'] ? 'selected' : '' }}>{{ $dairy['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Animal</label>
                    <select name="animal_id" class="form-select">
                        <option value="0">All Animals</option>
                        @foreach($options['animals'] as $animal)
                            <option value="{{ $animal['id'] }}" {{ $filters['animal_id'] === $animal['id'] ? 'selected' : '' }}>{{ $animal['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>All</option>
                        <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $filters['status'] === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Village</label>
                    <select name="village" class="form-select">
                        <option value="">All Villages</option>
                        @foreach($options['villages'] as $village)
                            <option value="{{ $village }}" {{ $filters['village'] === $village ? 'selected' : '' }}>{{ $village }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">District</label>
                    <select name="district" class="form-select">
                        <option value="">All Districts</option>
                        @foreach($options['districts'] as $district)
                            <option value="{{ $district }}" {{ $filters['district'] === $district ? 'selected' : '' }}>{{ $district }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">State</label>
                    <select name="state" class="form-select">
                        <option value="">All States</option>
                        @foreach($options['states'] as $state)
                            <option value="{{ $state }}" {{ $filters['state'] === $state ? 'selected' : '' }}>{{ $state }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date', $context['start']->toDateString()) }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date', $context['end']->toDateString()) }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass me-1"></i> Search</button>
                        <a href="{{ route('analytics.farmer') }}" class="btn btn-light border">Reset</a>
                        <a href="{{ $pdfExportUrl }}" target="_blank" class="btn btn-light border"><i class="fa-solid fa-file-pdf text-danger"></i></a>
                        <a href="{{ $excelExportUrl }}" class="btn btn-light border"><i class="fa-solid fa-file-excel text-success"></i></a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="mb-1">Growth Overview</h5>
                <p class="text-muted mb-0">Daily, weekly, monthly, or yearly growth based on your selected period.</p>
            </div>
            <div class="card-body">
                <div id="farmer-growth-chart" class="apex-charts"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="mb-1">Operational Coverage</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="rounded-3 bg-light p-3">
                            <small class="text-muted d-block">Animals</small>
                            <strong class="fs-4">{{ number_format($animalsCount) }}</strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="rounded-3 bg-light p-3">
                            <small class="text-muted d-block">Dairies</small>
                            <strong class="fs-4">{{ number_format($dairiesCount) }}</strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="rounded-3 bg-light p-3">
                            <small class="text-muted d-block">Feedings</small>
                            <strong class="fs-4">{{ number_format($feedingRecordsCount) }}</strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="rounded-3 bg-light p-3">
                            <small class="text-muted d-block">Pregnancy</small>
                            <strong class="fs-4">{{ number_format($pregnancyRecordsCount) }}</strong>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="rounded-3 bg-light p-3">
                            <small class="text-muted d-block">Health Records</small>
                            <strong class="fs-4">{{ number_format($healthRecordsCount) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="mb-1">Area Wise Distribution</h5>
                <p class="text-muted mb-0">Top {{ strtolower($areaWiseLabel) }} performance by farmer count.</p>
            </div>
            <div class="card-body">
                <div id="farmer-area-chart" class="apex-charts mb-3"></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ $areaWiseLabel }}</th>
                                <th class="text-end">Farmers</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($areaWiseRows as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="text-end">{{ $row['total'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted">No area data available.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="mb-1">Village Activity</h5>
                <p class="text-muted mb-0">Combined milk, feeding, pregnancy, and health activity score.</p>
            </div>
            <div class="card-body">
                <div id="village-activity-chart" class="apex-charts mb-3"></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Village</th>
                                <th class="text-end">Activity Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topVillageRows as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="text-end">{{ $row['total'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted">No village activity found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 pb-0">
        <h5 class="mb-1">Top Performing Farmers</h5>
        <p class="text-muted mb-0">Ranked by milk revenue with supporting activity details.</p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Farmer</th>
                        <th>Village</th>
                        <th>Animals</th>
                        <th>Milk</th>
                        <th>Revenue</th>
                        <th>Feedings</th>
                        <th>Last App Activity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topPerformingFarmers as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['name'] }}</td>
                            <td>{{ $row['village'] }}</td>
                            <td>{{ $row['animals_count'] }}</td>
                            <td>{{ number_format($row['milk_liters'], 2) }} L</td>
                            <td>Rs {{ number_format($row['revenue'], 2) }}</td>
                            <td>{{ $row['feeding_records'] }}</td>
                            <td>{{ $row['last_activity'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No farmer performance data available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const growthOptions = {
        chart: { type: 'line', height: 320, toolbar: { show: false } },
        series: [
            { name: 'Farmer Growth', data: @json($farmerGrowthSeries) },
            { name: 'Milk Growth (L)', data: @json($milkGrowthSeries) },
            { name: 'Revenue', data: @json($revenueSeries) },
        ],
        xaxis: { categories: @json($bucketLabels) },
        stroke: { curve: 'smooth', width: 3 },
        colors: ['#0f766e', '#f59e0b', '#2563eb'],
        dataLabels: { enabled: false },
        legend: { position: 'top', horizontalAlign: 'left' },
        yaxis: [
            { title: { text: 'Count / Liters' } },
            { opposite: true, title: { text: 'Revenue' } }
        ]
    };

    const areaOptions = {
        chart: { type: 'bar', height: 280, toolbar: { show: false } },
        series: [{ name: 'Farmers', data: @json(collect($areaWiseRows)->pluck('total')) }],
        xaxis: { categories: @json(collect($areaWiseRows)->pluck('label')) },
        colors: ['#14b8a6'],
        plotOptions: { bar: { borderRadius: 6, horizontal: true } },
        dataLabels: { enabled: false }
    };

    const villageOptions = {
        chart: { type: 'bar', height: 280, toolbar: { show: false } },
        series: [{ name: 'Activity Score', data: @json(collect($topVillageRows)->pluck('total')) }],
        xaxis: { categories: @json(collect($topVillageRows)->pluck('label')) },
        colors: ['#f97316'],
        plotOptions: { bar: { borderRadius: 6 } },
        dataLabels: { enabled: false }
    };

    const growthEl = document.querySelector('#farmer-growth-chart');
    if (growthEl) new ApexCharts(growthEl, growthOptions).render();
    const areaEl = document.querySelector('#farmer-area-chart');
    if (areaEl) new ApexCharts(areaEl, areaOptions).render();
    const villageEl = document.querySelector('#village-activity-chart');
    if (villageEl) new ApexCharts(villageEl, villageOptions).render();
});
</script>
@endpush
