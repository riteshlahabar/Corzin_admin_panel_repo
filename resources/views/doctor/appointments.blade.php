@extends('layouts.app')
@section('title', 'Doctor Appointments')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h4 class="mb-0 text-dark">Appointment</h4>
        <small class="text-muted">Appointments created by farmer are shown here.</small>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Total</p>
                    <h4 class="mb-0">{{ $summary['total'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Pending</p>
                    <h4 class="mb-0 text-warning">{{ $summary['pending'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Approved</p>
                    <h4 class="mb-0 text-success">{{ $summary['approved'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Completed</p>
                    <h4 class="mb-0 text-primary">{{ $summary['completed'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('doctor.appointments') }}" class="row g-2 mb-3">
                <div class="col-md-6">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        class="form-control"
                        placeholder="Search farmer, animal, concern, status..."
                    >
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        @foreach(['pending', 'proposed', 'approved', 'scheduled', 'in_progress', 'cancelled'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-success">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Farmer</th>
                            <th>Animal</th>
                            <th>Concern</th>
                            <th>On-Site Treatment</th>
                            <th>Requested</th>
                            <th>Doctor</th>
                            <th>Scheduled</th>
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
                                <td>{{ $appointments->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $appointment->farmer_name ?: '-' }}</div>
                                    <small class="text-muted">{{ $appointment->farmer_phone ?: '-' }}</small>
                                </td>
                                <td>{{ $appointment->animal_name ?: '-' }}</td>
                                <td style="min-width: 220px;">{{ $appointment->concern ?: '-' }}</td>
                                <td style="min-width: 220px;">{{ $appointment->onsite_treatment ?: '-' }}</td>
                                <td>{{ optional($appointment->requested_at ?: $appointment->created_at)->format('d-m-Y h:i A') ?: '-' }}</td>
                                <td style="min-width: 240px;">
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
                                <td>{{ optional($appointment->scheduled_at)->format('d-m-Y h:i A') ?: '-' }}</td>
                                <td>{{ $appointment->charges !== null ? '₹ '.number_format((float) $appointment->charges, 2) : '-' }}</td>
                                <td><span class="badge {{ $badgeClass }}">{{ ucwords(str_replace('_', ' ', $status)) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No appointments found</td>
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
@endsection
