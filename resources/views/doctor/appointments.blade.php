@extends('layouts.app')
@section('title', 'Doctor Appointments')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 mt-4 pt-2">
        <h4 class="mb-0 text-dark">Appointment</h4>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="background: #eef5ff; border-left: 4px solid #2f80ed !important;">
                <div class="card-body">
                    <p class="text-muted mb-1">Total</p>
                    <h4 class="mb-0">{{ $summary['total'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="background: #fff8e8; border-left: 4px solid #f5a623 !important;">
                <div class="card-body">
                    <p class="text-muted mb-1">Pending</p>
                    <h4 class="mb-0 text-warning">{{ $summary['pending'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="background: #ecfdf3; border-left: 4px solid #27ae60 !important;">
                <div class="card-body">
                    <p class="text-muted mb-1">Approved</p>
                    <h4 class="mb-0 text-success">{{ $summary['approved'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="background: #f0f7ff; border-left: 4px solid #2d9cdb !important;">
                <div class="card-body">
                    <p class="text-muted mb-1">Completed</p>
                    <h4 class="mb-0 text-primary">{{ $summary['completed'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form id="appointmentsSearchForm" method="GET" action="{{ route('doctor.appointments') }}" class="row g-2 mb-3">
                <div class="col-md-4 col-lg-3">
                    <input
                        id="appointmentsSearchInput"
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        class="form-control"
                        placeholder="Search farmer, animal, disease, doctor..."
                    >
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Appointment ID</th>
                            <th>Farmer</th>
                            <th>Animal</th>
                            <th>Disease</th>
                            <th>Created At</th>
                            <th>Doctor</th>
                            <th>Charges</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($appointments as $appointment)
                            @php
                                $status = strtolower($appointment->status ?? 'pending');
                                $allowAssign = in_array($status, ['pending', 'new', 'requested', 'proposed', 'awaiting_farmer_approval', 'awaiting_approval'], true);
                                $badgeClass = match ($status) {
                                    'approved', 'scheduled', 'in_progress' => 'bg-success',
                                    'completed' => 'bg-primary',
                                    'proposed' => 'bg-warning text-dark',
                                    'cancelled', 'rejected' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                            @endphp
                            <tr>
                                <td><span class="fw-semibold">{{ $appointment->appointment_code }}</span></td>
                                <td>
                                    <div class="fw-semibold">{{ $appointment->farmer_name ?: '-' }}</div>
                                    <small class="text-muted">{{ $appointment->farmer_phone ?: '-' }}</small>
                                </td>
                                <td>{{ $appointment->animal_name ?: '-' }}</td>
                                <td>{{ $appointment->concern ?: '-' }}</td>
                                <td>{{ optional($appointment->requested_at ?: $appointment->created_at)->format('d-m-Y h:i A') ?: '-' }}</td>
                                <td>
                                    @if($allowAssign)
                                        <form method="POST" action="{{ route('doctor.appointments.assign', $appointment) }}" class="d-flex gap-2">
                                            @csrf
                                            <select name="doctor_id" class="form-select form-select-sm" required>
                                                <option value="">Select doctor</option>
                                                @foreach($doctors as $doctor)
                                                    @php
                                                        $doctorName = trim(($doctor->first_name ?? '').' '.($doctor->last_name ?? ''));
                                                    @endphp
                                                    <option value="{{ $doctor->id }}" {{ (int) $appointment->doctor_id === (int) $doctor->id ? 'selected' : '' }}>
                                                        {{ $doctorName !== '' ? $doctorName : ($doctor->name ?: 'Doctor #'.$doctor->id) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-success btn-sm">Assign</button>
                                        </form>
                                    @else
                                        {{ optional($appointment->doctor)->full_name ?: optional($appointment->doctor)->name ?: '-' }}
                                    @endif
                                </td>
                                <td>{{ $appointment->charges !== null ? 'Rs '.number_format((float) $appointment->charges, 2) : '-' }}</td>
                                <td><span class="badge {{ $badgeClass }}">{{ ucwords(str_replace('_', ' ', $status)) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No appointments found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($appointments->hasPages())
            <div class="card-footer bg-white">
                {{ $appointments->links() }}
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('appointmentsSearchInput');
    const form = document.getElementById('appointmentsSearchForm');
    if (!input || !form) return;

    let timer = null;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            form.submit();
        }, 350);
    });
});
</script>
@endsection
