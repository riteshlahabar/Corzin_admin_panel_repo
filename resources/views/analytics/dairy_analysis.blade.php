@extends('layouts.app')
@section('title', 'Dairy Analysis')

@php
    $pdfExportUrl = request()->fullUrlWithQuery(['export' => 'pdf']);
    $excelExportUrl = request()->fullUrlWithQuery(['export' => 'excel']);
@endphp

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box d-md-flex justify-content-md-between align-items-center">
            <div>
                <h4 class="page-title mb-1">Dairy Analysis</h4>
                <p class="text-muted mb-0">Track milk collection, paid amount, pending amount, and which farmer is linked to which dairy.</p>
            </div>
            <div>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Corzin</a></li>
                    <li class="breadcrumb-item"><a href="#">Analytics</a></li>
                    <li class="breadcrumb-item active">Dairy Analysis</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm"><div class="card-body"><small class="text-uppercase text-muted fw-semibold">Total Dairies</small><h3 class="mt-2 mb-1">{{ number_format($totalDairies) }}</h3><span class="text-muted">Filtered dairy base</span></div></div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm"><div class="card-body"><small class="text-uppercase text-muted fw-semibold">Active Dairies</small><h3 class="mt-2 mb-1">{{ number_format($activeDairies) }}</h3><span class="text-muted">{{ number_format($attachedFarmers) }} farmers attached</span></div></div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm"><div class="card-body"><small class="text-uppercase text-muted fw-semibold">Milk Collection</small><h3 class="mt-2 mb-1">{{ number_format($milkVolume, 2) }} L</h3><span class="{{ $milkGrowthRate >= 0 ? 'text-success' : 'text-danger' }}">{{ $milkGrowthRate >= 0 ? '+' : '' }}{{ $milkGrowthRate }}% vs previous</span></div></div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm"><div class="card-body"><small class="text-uppercase text-muted fw-semibold">Paid Amount</small><h3 class="mt-2 mb-1">Rs {{ number_format($paidAmount, 2) }}</h3><span class="{{ $paymentGrowthRate >= 0 ? 'text-success' : 'text-danger' }}">{{ $paymentGrowthRate >= 0 ? '+' : '' }}{{ $paymentGrowthRate }}% vs previous</span></div></div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('analytics.dairy') }}">
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
                    <label class="form-label">Dairy</label>
                    <select name="dairy_id" class="form-select">
                        <option value="0">All Dairies</option>
                        @foreach($options['dairies'] as $dairy)
                            <option value="{{ $dairy['id'] }}" {{ $filters['dairy_id'] === $dairy['id'] ? 'selected' : '' }}>{{ $dairy['label'] }}</option>
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
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>All</option>
                        <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $filters['status'] === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                        <a href="{{ route('analytics.dairy') }}" class="btn btn-light border">Reset</a>
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
            <div class="card-header bg-transparent border-0 pb-0"><h5 class="mb-1">Dairy Trend Overview</h5></div>
            <div class="card-body"><div id="dairy-trend-chart" class="apex-charts"></div></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0"><h5 class="mb-1">Finance Snapshot</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12"><div class="rounded-3 bg-light p-3"><small class="text-muted d-block">Total Amount</small><strong class="fs-4">Rs {{ number_format($totalAmount, 2) }}</strong></div></div>
                    <div class="col-12"><div class="rounded-3 bg-light p-3"><small class="text-muted d-block">Pending Amount</small><strong class="fs-4">Rs {{ number_format($pendingAmount, 2) }}</strong></div></div>
                    <div class="col-12"><div class="rounded-3 bg-light p-3"><small class="text-muted d-block">Collection Rate</small><strong class="fs-4">{{ $collectionRate }}%</strong></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="mb-1">Dairy Performance Table</h5>
                <p class="text-muted mb-0">Shows which farmer is attached to which dairy along with milk and payment figures.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Dairy</th>
                                <th>Farmer</th>
                                <th>District</th>
                                <th>State</th>
                                <th>Status</th>
                                <th>Milk</th>
                                <th>Total Amount</th>
                                <th>Paid</th>
                                <th>Pending</th>
                                <th>Latest Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dairyRows as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row['dairy_name'] }}</td>
                                    <td>{{ $row['farmer_name'] }}</td>
                                    <td>{{ $row['district'] }}</td>
                                    <td>{{ $row['state'] }}</td>
                                    <td>{{ $row['status'] }}</td>
                                    <td>{{ number_format($row['milk_total'], 2) }} L</td>
                                    <td>Rs {{ number_format($row['total_amount'], 2) }}</td>
                                    <td>Rs {{ number_format($row['paid_amount'], 2) }}</td>
                                    <td>Rs {{ number_format($row['pending_amount'], 2) }}</td>
                                    <td>{{ $row['latest_payment_date'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="text-center text-muted">No dairy report data available.</td></tr>
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
    const chartOptions = {
        chart: { type: 'line', height: 320, toolbar: { show: false } },
        series: [
            { name: 'Dairy Growth', data: @json($dairyGrowthSeries) },
            { name: 'Milk Collection (L)', data: @json($milkSeries) },
            { name: 'Paid Amount', data: @json($paymentSeries) },
        ],
        xaxis: { categories: @json($bucketLabels) },
        stroke: { curve: 'smooth', width: 3 },
        colors: ['#0ea5e9', '#16a34a', '#f97316'],
        dataLabels: { enabled: false },
        legend: { position: 'top', horizontalAlign: 'left' }
    };

    const chartEl = document.querySelector('#dairy-trend-chart');
    if (chartEl) new ApexCharts(chartEl, chartOptions).render();
});
</script>
@endpush
