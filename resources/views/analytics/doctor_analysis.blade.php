@extends('layouts.app')
@section('title', 'Doctor Analysis')

@php
    $pdfExportUrl = request()->fullUrlWithQuery(['export' => 'pdf']);
    $excelExportUrl = request()->fullUrlWithQuery(['export' => 'excel']);
@endphp

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box d-md-flex justify-content-md-between align-items-center">
            <div>
                <h4 class="page-title mb-1">Doctor Analysis</h4>
                <p class="text-muted mb-0">Doctor growth, appointment outcomes, revenue, retention, and live activity in one place.</p>
            </div>
            <div>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Corzin</a></li>
                    <li class="breadcrumb-item"><a href="#">Analytics</a></li>
                    <li class="breadcrumb-item active">Doctor Analysis</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6 col-lg-3"><div class="card h-100 border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #0f766e, #2dd4bf);"><div class="card-body"><small class="text-uppercase fw-semibold" style="color: rgba(255,255,255,.8);">Total Doctors</small><h3 class="mt-2 mb-1">{{ number_format($totalDoctors) }}</h3><span style="color: rgba(255,255,255,.82);">{{ number_format($newDoctors) }} new in period</span></div></div></div>
    <div class="col-md-6 col-lg-3"><div class="card h-100 border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #1d4ed8, #60a5fa);"><div class="card-body"><small class="text-uppercase fw-semibold" style="color: rgba(255,255,255,.8);">Active Doctors</small><h3 class="mt-2 mb-1">{{ number_format($activeDoctors) }}</h3><span style="color: rgba(255,255,255,.82);">Live or appointment activity</span></div></div></div>
    <div class="col-md-6 col-lg-3"><div class="card h-100 border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #7c3aed, #a78bfa);"><div class="card-body"><small class="text-uppercase fw-semibold" style="color: rgba(255,255,255,.8);">Retention Rate</small><h3 class="mt-2 mb-1">{{ $retentionRate }}%</h3><span style="color: rgba(255,255,255,.82);">Compared with previous period</span></div></div></div>
    <div class="col-md-6 col-lg-3"><div class="card h-100 border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #b45309, #f59e0b);"><div class="card-body"><small class="text-uppercase fw-semibold" style="color: rgba(255,255,255,.8);">Doctor Revenue</small><h3 class="mt-2 mb-1">Rs {{ number_format($doctorRevenue, 2) }}</h3><span style="color: rgba(255,255,255,.82);">{{ $revenueGrowthRate >= 0 ? '+' : '' }}{{ $revenueGrowthRate }}% vs previous</span></div></div></div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('analytics.doctor') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Period</label>
                    <select name="period" class="form-select">
                        @foreach($periodOptions as $periodOption)
                            <option value="{{ $periodOption['value'] }}" {{ $filters['period'] === $periodOption['value'] ? 'selected' : '' }}>{{ $periodOption['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Doctor</label>
                    <select name="doctor_id" class="form-select">
                        <option value="0">All Doctors</option>
                        @foreach($options['doctors'] as $doctor)
                            <option value="{{ $doctor['id'] }}" {{ $filters['doctor_id'] === $doctor['id'] ? 'selected' : '' }}>{{ $doctor['label'] }}</option>
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
                        <option value="approved" {{ $filters['status'] === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="rejected" {{ $filters['status'] === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="declined" {{ $filters['status'] === 'declined' ? 'selected' : '' }}>Declined</option>
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
                        <a href="{{ route('analytics.doctor') }}" class="btn btn-light border">Reset</a>
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
            <div class="card-header bg-transparent border-0 pb-0"><h5 class="mb-1">Doctor Performance Trend</h5></div>
            <div class="card-body"><div id="doctor-trend-chart" class="apex-charts"></div></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0"><h5 class="mb-1">Status & Outcome</h5></div>
            <div class="card-body">
                <div id="doctor-status-chart" class="apex-charts mb-3"></div>
                <div class="row g-2">
                    <div class="col-6"><div class="rounded-3 bg-light p-3 text-center"><small class="text-muted d-block">Approval</small><strong>{{ $approvalRate }}%</strong></div></div>
                    <div class="col-6"><div class="rounded-3 bg-light p-3 text-center"><small class="text-muted d-block">Completion</small><strong>{{ $completionRate }}%</strong></div></div>
                    <div class="col-6"><div class="rounded-3 bg-light p-3 text-center"><small class="text-muted d-block">Appointments</small><strong>{{ number_format($totalAppointments) }}</strong></div></div>
                    <div class="col-6"><div class="rounded-3 bg-light p-3 text-center"><small class="text-muted d-block">On-site</small><strong>{{ number_format($onSiteTreatments) }}</strong></div></div>
                    <div class="col-12"><div class="rounded-3 bg-light p-3 text-center"><small class="text-muted d-block">Follow-ups</small><strong>{{ number_format($followups) }}</strong></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 pb-0">
        <h5 class="mb-1">Doctor Performance Table</h5>
        <p class="text-muted mb-0">Filtered doctor report with appointments, completion, on-site treatment, revenue, and last live activity.</p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Doctor</th>
                        <th>City</th>
                        <th>District</th>
                        <th>State</th>
                        <th>Status</th>
                        <th>Appointments</th>
                        <th>Completed</th>
                        <th>Completion</th>
                        <th>On-site</th>
                        <th>Revenue</th>
                        <th>Last Live</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($doctorRows as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['name'] }}</td>
                            <td>{{ $row['city'] }}</td>
                            <td>{{ $row['district'] }}</td>
                            <td>{{ $row['state'] }}</td>
                            <td>{{ ucfirst($row['status']) }}</td>
                            <td>{{ $row['appointments'] }}</td>
                            <td>{{ $row['completed'] }}</td>
                            <td>{{ $row['completion_rate'] }}%</td>
                            <td>{{ $row['onsite_treatments'] }}</td>
                            <td>Rs {{ number_format($row['revenue'], 2) }}</td>
                            <td>{{ $row['last_live'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted">No doctor report data available.</td></tr>
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
    const trendOptions = {
        chart: { type: 'line', height: 320, toolbar: { show: false } },
        series: [
            { name: 'Doctor Growth', data: @json($doctorGrowthSeries) },
            { name: 'Appointments', data: @json($appointmentGrowthSeries) },
            { name: 'Revenue', data: @json($revenueSeries) },
        ],
        xaxis: { categories: @json($bucketLabels) },
        stroke: { curve: 'smooth', width: 3 },
        colors: ['#0ea5e9', '#22c55e', '#f97316'],
        dataLabels: { enabled: false },
        legend: { position: 'top', horizontalAlign: 'left' }
    };

    const statusOptions = {
        chart: { type: 'donut', height: 280 },
        labels: @json($statusLabels),
        series: @json($statusSeries),
        colors: ['#22c55e', '#f59e0b', '#ef4444', '#94a3b8'],
        legend: { position: 'bottom' },
        dataLabels: { enabled: true }
    };

    const trendEl = document.querySelector('#doctor-trend-chart');
    if (trendEl) new ApexCharts(trendEl, trendOptions).render();
    const statusEl = document.querySelector('#doctor-status-chart');
    if (statusEl) new ApexCharts(statusEl, statusOptions).render();
});
</script>
@endpush
