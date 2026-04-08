@extends('layouts.app')
@section('title', 'Farmer Analysis')

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box d-md-flex justify-content-md-between align-items-center">
            <h4 class="page-title">Farmer Analysis</h4>
            <div>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Corzin</a></li>
                    <li class="breadcrumb-item"><a href="#">Analytics</a></li>
                    <li class="breadcrumb-item active">Farmer Analysis</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="row d-flex justify-content-center"><div class="col-9"><p class="text-dark mb-0 fw-semibold fs-14">Total Farmers</p><h3 class="mt-2 mb-0 fw-bold">{{ number_format($totalFarmers) }}</h3></div><div class="col-3 align-self-center"><div class="d-flex justify-content-center align-items-center thumb-xl bg-primary rounded-circle mx-auto"><i class="iconoir-group h1 align-self-center mb-0 text-white"></i></div></div></div></div></div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="row d-flex justify-content-center"><div class="col-9"><p class="text-dark mb-0 fw-semibold fs-14">Active Farmers</p><h3 class="mt-2 mb-0 fw-bold">{{ number_format($activeFarmers) }}</h3></div><div class="col-3 align-self-center"><div class="d-flex justify-content-center align-items-center thumb-xl bg-success rounded-circle mx-auto"><i class="iconoir-check-circle h1 align-self-center mb-0 text-white"></i></div></div></div></div></div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="row d-flex justify-content-center"><div class="col-9"><p class="text-dark mb-0 fw-semibold fs-14">New This Month</p><h3 class="mt-2 mb-0 fw-bold">{{ number_format($newFarmers) }}</h3></div><div class="col-3 align-self-center"><div class="d-flex justify-content-center align-items-center thumb-xl bg-warning rounded-circle mx-auto"><i class="iconoir-user-plus h1 align-self-center mb-0 text-white"></i></div></div></div></div></div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="row d-flex justify-content-center"><div class="col-9"><p class="text-dark mb-0 fw-semibold fs-14">With Animals</p><h3 class="mt-2 mb-0 fw-bold">{{ number_format($farmersWithAnimals) }}</h3></div><div class="col-3 align-self-center"><div class="d-flex justify-content-center align-items-center thumb-xl bg-info rounded-circle mx-auto"><i class="iconoir-paw h1 align-self-center mb-0 text-white"></i></div></div></div></div></div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 col-lg-8">
        <div class="card">
            <div class="card-header"><div class="row align-items-center"><div class="col"><h4 class="card-title">Farmer Growth</h4></div><div class="col-auto"><span class="badge bg-primary-subtle text-primary">Last 12 months</span></div></div></div>
            <div class="card-body pt-0"><div id="farmer-growth-line" class="apex-charts mb-3"></div></div>
        </div>
    </div>
    <div class="col-md-12 col-lg-4">
        <div class="row g-3">
            <div class="col-md-6 col-lg-6"><div class="card mb-3 mb-lg-0"><div class="card-body text-center"><span class="fs-18 fw-semibold">{{ number_format($totalFarmers) }}</span><h6 class="text-uppercase text-muted my-2 m-0">Total Farmers</h6><div class="d-flex align-items-center"><div class="progress bg-primary-subtle w-100" style="height:5px;"><div class="progress-bar bg-primary" style="width: 100%"></div></div><small class="flex-shrink-1 ms-1">100%</small></div></div></div></div>
            <div class="col-md-6 col-lg-6"><div class="card mb-3 mb-lg-0"><div class="card-body text-center"><span class="fs-18 fw-semibold">{{ $activeRate }}%</span><h6 class="text-uppercase text-muted my-2 m-0">Active Rate</h6><div class="d-flex align-items-center"><div class="progress bg-success-subtle w-100" style="height:5px;"><div class="progress-bar bg-success" style="width: {{ min($activeRate, 100) }}%"></div></div><small class="flex-shrink-1 ms-1">{{ $activeRate }}%</small></div></div></div></div>
            <div class="col-md-6 col-lg-6"><div class="card mb-3 mb-lg-0"><div class="card-body text-center"><span class="fs-18 fw-semibold">{{ $animalCoverage }}%</span><h6 class="text-uppercase text-muted my-2 m-0">Animal Coverage</h6><div class="d-flex align-items-center"><div class="progress bg-info-subtle w-100" style="height:5px;"><div class="progress-bar bg-info" style="width: {{ min($animalCoverage, 100) }}%"></div></div><small class="flex-shrink-1 ms-1">{{ $animalCoverage }}%</small></div></div></div></div>
            <div class="col-md-6 col-lg-6"><div class="card mb-3 mb-lg-0"><div class="card-body text-center"><span class="fs-18 fw-semibold">{{ $dairyCoverage }}%</span><h6 class="text-uppercase text-muted my-2 m-0">Dairy Coverage</h6><div class="d-flex align-items-center"><div class="progress bg-warning-subtle w-100" style="height:5px;"><div class="progress-bar bg-warning" style="width: {{ min($dairyCoverage, 100) }}%"></div></div><small class="flex-shrink-1 ms-1">{{ $dairyCoverage }}%</small></div></div></div></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Top States</h4></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light"><tr><th>State</th><th class="text-end">Farmers</th></tr></thead>
                        <tbody>
                            @forelse($topStates as $state)
                                <tr><td>{{ $state->state_name }}</td><td class="text-end">{{ $state->total }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-8">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Farmer Details</h4></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th><th>Mobile</th><th>City</th><th>Start Date</th><th>Animals</th><th>Dairies</th><th>Completion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($farmerRows as $row)
                                <tr>
                                    <td>{{ $row['name'] }}</td>
                                    <td>{{ $row['mobile'] }}</td>
                                    <td>{{ $row['city'] }}</td>
                                    <td>{{ $row['joined_at'] }}</td>
                                    <td>{{ $row['animals_count'] }}</td>
                                    <td>{{ $row['dairies_count'] }}</td>
                                    <td>{{ $row['completion'] }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">No farmers found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
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
            { name: 'Farmer Registrations', data: @json($farmerGrowthSeries) },
            { name: 'Animal Additions', data: @json($animalGrowthSeries) },
        ],
        xaxis: { categories: @json($monthLabels) },
        stroke: { curve: 'smooth', width: 3 },
        colors: ['#0ea5e9', '#16a34a'],
        dataLabels: { enabled: false },
        legend: { position: 'top', horizontalAlign: 'right' }
    };
    const el = document.querySelector('#farmer-growth-line');
    if (el) new ApexCharts(el, growthOptions).render();
});
</script>
@endpush

