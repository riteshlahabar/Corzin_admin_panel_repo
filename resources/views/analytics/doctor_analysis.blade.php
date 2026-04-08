@extends('layouts.app')
@section('title', 'Doctor Analysis')

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box d-md-flex justify-content-md-between align-items-center">
            <h4 class="page-title">Doctor Analysis</h4>
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

<div class="row">
    <div class="col-md-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="row d-flex justify-content-center"><div class="col-9"><p class="text-dark mb-0 fw-semibold fs-14">Total Doctors</p><h3 class="mt-2 mb-0 fw-bold">{{ number_format($totalDoctors) }}</h3></div><div class="col-3 align-self-center"><div class="d-flex justify-content-center align-items-center thumb-xl bg-primary rounded-circle mx-auto"><i class="iconoir-health-shield h1 align-self-center mb-0 text-white"></i></div></div></div></div></div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="row d-flex justify-content-center"><div class="col-9"><p class="text-dark mb-0 fw-semibold fs-14">Approved</p><h3 class="mt-2 mb-0 fw-bold">{{ number_format($approvedDoctors) }}</h3></div><div class="col-3 align-self-center"><div class="d-flex justify-content-center align-items-center thumb-xl bg-success rounded-circle mx-auto"><i class="iconoir-check-circle h1 align-self-center mb-0 text-white"></i></div></div></div></div></div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="row d-flex justify-content-center"><div class="col-9"><p class="text-dark mb-0 fw-semibold fs-14">Pending</p><h3 class="mt-2 mb-0 fw-bold">{{ number_format($pendingDoctors) }}</h3></div><div class="col-3 align-self-center"><div class="d-flex justify-content-center align-items-center thumb-xl bg-warning rounded-circle mx-auto"><i class="iconoir-hourglass h1 align-self-center mb-0 text-white"></i></div></div></div></div></div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="row d-flex justify-content-center"><div class="col-9"><p class="text-dark mb-0 fw-semibold fs-14">New This Month</p><h3 class="mt-2 mb-0 fw-bold">{{ number_format($newDoctors) }}</h3></div><div class="col-3 align-self-center"><div class="d-flex justify-content-center align-items-center thumb-xl bg-info rounded-circle mx-auto"><i class="iconoir-user-plus h1 align-self-center mb-0 text-white"></i></div></div></div></div></div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 col-lg-8">
        <div class="card">
            <div class="card-header"><div class="row align-items-center"><div class="col"><h4 class="card-title">Doctor Growth</h4></div><div class="col-auto"><span class="badge bg-primary-subtle text-primary">Last 12 months</span></div></div></div>
            <div class="card-body pt-0"><div id="doctor-growth-line" class="apex-charts mb-3"></div></div>
        </div>
    </div>
    <div class="col-md-12 col-lg-4">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Status Split</h4></div>
            <div class="card-body pt-0">
                <div id="doctor-status-donut" class="apex-charts mb-3"></div>
                <div class="row g-2">
                    <div class="col-6"><div class="p-2 bg-light rounded text-center"><small class="text-muted">Approval Rate</small><h6 class="mb-0">{{ $approvalRate }}%</h6></div></div>
                    <div class="col-6"><div class="p-2 bg-light rounded text-center"><small class="text-muted">Completion Rate</small><h6 class="mb-0">{{ $completionRate }}%</h6></div></div>
                    <div class="col-6"><div class="p-2 bg-light rounded text-center"><small class="text-muted">Appointments</small><h6 class="mb-0">{{ number_format($totalAppointments) }}</h6></div></div>
                    <div class="col-6"><div class="p-2 bg-light rounded text-center"><small class="text-muted">Completed</small><h6 class="mb-0">{{ number_format($completedAppointments) }}</h6></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Doctor Details</h4></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th><th>Contact</th><th>City</th><th>Status</th><th>Start Date</th><th>Appointments</th><th>Completion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($doctorRows as $row)
                                <tr>
                                    <td>{{ $row['name'] }}</td>
                                    <td>{{ $row['contact'] }}</td>
                                    <td>{{ $row['city'] }}</td>
                                    <td>
                                        <span class="badge {{ $row['status'] === 'approved' ? 'bg-success-subtle text-success' : ($row['status'] === 'pending' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger') }}">
                                            {{ ucfirst($row['status']) }}
                                        </span>
                                    </td>
                                    <td>{{ $row['joined_at'] }}</td>
                                    <td>{{ $row['appointments_count'] }}</td>
                                    <td>{{ $row['completion_rate'] }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">No doctors found</td></tr>
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
            { name: 'Doctor Registrations', data: @json($doctorGrowthSeries) },
            { name: 'Appointments', data: @json($appointmentGrowthSeries) },
        ],
        xaxis: { categories: @json($monthLabels) },
        stroke: { curve: 'smooth', width: 3 },
        colors: ['#06b6d4', '#22c55e'],
        dataLabels: { enabled: false },
        legend: { position: 'top', horizontalAlign: 'right' }
    };

    const statusOptions = {
        chart: { type: 'donut', height: 280 },
        labels: @json($statusLabels),
        series: @json($statusSeries),
        colors: ['#22c55e', '#f59e0b', '#ef4444', '#94a3b8'],
        legend: { position: 'bottom' },
        dataLabels: { enabled: true }
    };

    const lineEl = document.querySelector('#doctor-growth-line');
    if (lineEl) new ApexCharts(lineEl, growthOptions).render();
    const donutEl = document.querySelector('#doctor-status-donut');
    if (donutEl) new ApexCharts(donutEl, statusOptions).render();
});
</script>
@endpush

