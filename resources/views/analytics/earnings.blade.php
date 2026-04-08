@extends('layouts.app')
@section('title', 'Earnings Analysis')

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box d-md-flex justify-content-md-between align-items-center">
            <h4 class="page-title">Earnings Analysis</h4>
            <div>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Corzin</a></li>
                    <li class="breadcrumb-item"><a href="#">Analytics</a></li>
                    <li class="breadcrumb-item active">Earnings</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="row d-flex justify-content-center"><div class="col-9"><p class="text-dark mb-0 fw-semibold fs-14">Farmer Earnings</p><h3 class="mt-2 mb-0 fw-bold">Rs {{ number_format($totalFarmerEarning, 2) }}</h3><small class="{{ $farmerTrend >= 0 ? 'text-success' : 'text-danger' }}">{{ $farmerTrend >= 0 ? '+' : '' }}{{ $farmerTrend }}% this month</small></div><div class="col-3 align-self-center"><div class="d-flex justify-content-center align-items-center thumb-xl bg-success rounded-circle mx-auto"><i class="iconoir-droplet h1 align-self-center mb-0 text-white"></i></div></div></div></div></div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="row d-flex justify-content-center"><div class="col-9"><p class="text-dark mb-0 fw-semibold fs-14">Doctor Earnings</p><h3 class="mt-2 mb-0 fw-bold">Rs {{ number_format($totalDoctorEarning, 2) }}</h3><small class="{{ $doctorTrend >= 0 ? 'text-success' : 'text-danger' }}">{{ $doctorTrend >= 0 ? '+' : '' }}{{ $doctorTrend }}% this month</small></div><div class="col-3 align-self-center"><div class="d-flex justify-content-center align-items-center thumb-xl bg-primary rounded-circle mx-auto"><i class="iconoir-stethoscope h1 align-self-center mb-0 text-white"></i></div></div></div></div></div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="row d-flex justify-content-center"><div class="col-9"><p class="text-dark mb-0 fw-semibold fs-14">Combined Earnings</p><h3 class="mt-2 mb-0 fw-bold">Rs {{ number_format($totalCombinedEarning, 2) }}</h3><small class="{{ $combinedTrend >= 0 ? 'text-success' : 'text-danger' }}">{{ $combinedTrend >= 0 ? '+' : '' }}{{ $combinedTrend }}% this month</small></div><div class="col-3 align-self-center"><div class="d-flex justify-content-center align-items-center thumb-xl bg-info rounded-circle mx-auto"><i class="iconoir-dollar-circle h1 align-self-center mb-0 text-white"></i></div></div></div></div></div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="row d-flex justify-content-center"><div class="col-9"><p class="text-dark mb-0 fw-semibold fs-14">This Month</p><h3 class="mt-2 mb-0 fw-bold">Rs {{ number_format($thisMonthCombined, 2) }}</h3><small class="text-muted">Farmer: Rs {{ number_format($thisMonthFarmer, 2) }} | Doctor: Rs {{ number_format($thisMonthDoctor, 2) }}</small></div><div class="col-3 align-self-center"><div class="d-flex justify-content-center align-items-center thumb-xl bg-warning rounded-circle mx-auto"><i class="iconoir-calendar h1 align-self-center mb-0 text-white"></i></div></div></div></div></div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 col-lg-8">
        <div class="card">
            <div class="card-header"><div class="row align-items-center"><div class="col"><h4 class="card-title">Earnings Growth</h4></div><div class="col-auto"><span class="badge bg-primary-subtle text-primary">Last 12 months</span></div></div></div>
            <div class="card-body pt-0"><div id="earnings-growth-line" class="apex-charts mb-3"></div></div>
        </div>
    </div>
    <div class="col-md-12 col-lg-4">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Earning Split</h4></div>
            <div class="card-body pt-0">
                <div id="earnings-split-donut" class="apex-charts mb-3"></div>
                <div class="row g-2">
                    <div class="col-6"><div class="p-2 bg-light rounded text-center"><small class="text-muted">Farmer</small><h6 class="mb-0">Rs {{ number_format($totalFarmerEarning, 0) }}</h6></div></div>
                    <div class="col-6"><div class="p-2 bg-light rounded text-center"><small class="text-muted">Doctor</small><h6 class="mb-0">Rs {{ number_format($totalDoctorEarning, 0) }}</h6></div></div>
                    <div class="col-12"><div class="p-2 bg-light rounded text-center"><small class="text-muted">Combined</small><h6 class="mb-0">Rs {{ number_format($totalCombinedEarning, 0) }}</h6></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 col-lg-8">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Monthly Earnings Breakdown</h4></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr><th>Month</th><th>Farmer Earnings</th><th>Doctor Earnings</th><th>Combined</th></tr>
                        </thead>
                        <tbody>
                            @forelse($breakdownRows as $row)
                                <tr>
                                    <td>{{ $row['month'] }}</td>
                                    <td>Rs {{ number_format($row['farmer'], 2) }}</td>
                                    <td>Rs {{ number_format($row['doctor'], 2) }}</td>
                                    <td class="fw-semibold">Rs {{ number_format($row['combined'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">No earnings data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12 col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><h4 class="card-title mb-0">Top Doctor Earnings</h4></div>
            <div class="card-body pt-0">
                <ul class="list-group list-group-flush">
                    @forelse($topDoctorEarnings as $item)
                        <li class="list-group-item d-flex justify-content-between px-0"><span>{{ $item['label'] }}</span><strong>Rs {{ number_format($item['total'], 2) }}</strong></li>
                    @empty
                        <li class="list-group-item px-0 text-muted">No doctor earnings yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Top Dairy Earnings</h4></div>
            <div class="card-body pt-0">
                <ul class="list-group list-group-flush">
                    @forelse($topDairyEarnings as $item)
                        <li class="list-group-item d-flex justify-content-between px-0"><span>{{ $item['label'] }}</span><strong>Rs {{ number_format($item['total'], 2) }}</strong></li>
                    @empty
                        <li class="list-group-item px-0 text-muted">No dairy earnings yet.</li>
                    @endforelse
                </ul>
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
        chart: { type: 'area', height: 320, toolbar: { show: false } },
        series: [
            { name: 'Farmer Earnings', data: @json($farmerSeries) },
            { name: 'Doctor Earnings', data: @json($doctorSeries) },
            { name: 'Combined Earnings', data: @json($combinedSeries) },
        ],
        xaxis: { categories: @json($monthLabels) },
        stroke: { curve: 'smooth', width: 2 },
        colors: ['#16a34a', '#0284c7', '#7c3aed'],
        fill: { type: 'gradient', gradient: { opacityFrom: 0.25, opacityTo: 0.05 } },
        dataLabels: { enabled: false },
        yaxis: { labels: { formatter: function(val){ return 'Rs ' + Number(val).toFixed(0); } } },
        tooltip: { y: { formatter: function(val){ return 'Rs ' + Number(val).toFixed(2); } } }
    };

    const splitOptions = {
        chart: { type: 'donut', height: 280 },
        labels: ['Farmer Earnings', 'Doctor Earnings'],
        series: [@json($totalFarmerEarning), @json($totalDoctorEarning)],
        colors: ['#22c55e', '#0ea5e9'],
        legend: { position: 'bottom' },
        dataLabels: { enabled: true }
    };

    const lineEl = document.querySelector('#earnings-growth-line');
    if (lineEl) new ApexCharts(lineEl, growthOptions).render();
    const donutEl = document.querySelector('#earnings-split-donut');
    if (donutEl) new ApexCharts(donutEl, splitOptions).render();
});
</script>
@endpush

