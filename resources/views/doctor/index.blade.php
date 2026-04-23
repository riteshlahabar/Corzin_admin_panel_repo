@extends('layouts.app')
@section('title', 'Doctor Module')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-4 mt-2">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 doctor-stat-card doctor-stat-card--blue">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="doctor-stat-label mb-1">Total Doctors</p>
                            <h3 class="mb-0">{{ $summary['total'] }}</h3>
                        </div>
                        <div class="avatar-md doctor-stat-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="iconoir-group fs-22"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 doctor-stat-card doctor-stat-card--green">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="doctor-stat-label mb-1">Approved Doctors</p>
                            <h3 class="mb-0">{{ $summary['available'] }}</h3>
                        </div>
                        <div class="avatar-md doctor-stat-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="iconoir-check-circle fs-22"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 doctor-stat-card doctor-stat-card--amber">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="doctor-stat-label mb-1">Pending Approval</p>
                            <h3 class="mb-0">{{ max(($summary['total'] ?? 0) - ($summary['available'] ?? 0), 0) }}</h3>
                        </div>
                        <div class="avatar-md doctor-stat-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="iconoir-clock fs-22"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 doctor-stat-card doctor-stat-card--cyan">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="doctor-stat-label mb-1">Covered Cities</p>
                            <h3 class="mb-0">{{ $summary['locations'] }}</h3>
                        </div>
                        <div class="avatar-md doctor-stat-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="iconoir-map-pin fs-22"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 pb-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="page-title mb-0">Doctor List</h4>
                <div class="doctor-toolbar doctor-toolbar--compact">
                    <div class="btn-group" role="group" aria-label="Doctor status filters">
                        <input type="checkbox" class="btn-check" id="filterApproved" autocomplete="off" checked>
                        <label class="btn btn-outline-success doctor-filter-btn" for="filterApproved">Approved</label>

                        <input type="checkbox" class="btn-check" id="filterUnapproved" autocomplete="off" checked>
                        <label class="btn btn-outline-success doctor-filter-btn" for="filterUnapproved">Unapproved</label>
                    </div>

                    <input type="text" id="doctorSearch" class="form-control doctor-search-input" placeholder="Search Doctor...">

                    <div class="input-group doctor-date-group">
                        <input type="date" id="doctorDateFrom" class="form-control" title="From date">
                        <span class="input-group-text">to</span>
                        <input type="date" id="doctorDateTo" class="form-control" title="To date">
                    </div>

                    <button type="button" class="btn btn-light border doctor-action-btn" id="exportDoctorPdf" title="Export PDF">
                        <i class="fa-solid fa-file-pdf text-danger"></i>
                    </button>
                    <button type="button" class="btn btn-light border doctor-action-btn" id="exportDoctorExcel" title="Export Excel">
                        <i class="fa-solid fa-file-excel text-success"></i>
                    </button>
                    <a href="{{ route('doctor.create') }}" class="btn btn-success doctor-add-btn">
                        <i class="fa-solid fa-plus me-1"></i> Add Doctor
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body pt-3">
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="doctorTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Clinic Name</th>
                            <th>Degree</th>
                            <th>MMC Reg No.</th>
                            <th>City</th>
                            <th>Contact</th>
                            <th>Register Date</th>
                            <th>Status</th>
                            <th>Status Button</th>
                            <th>Active / Inactive</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($doctors as $key => $doctor)
                            @php
                                $doctorStatus = ($doctor->status ?? 'pending') === 'approved' ? 'approved' : 'unapproved';
                                $doctorDate = optional($doctor->created_at)->format('Y-m-d');
                            @endphp
                            <tr class="doctor-row" data-status="{{ $doctorStatus }}" data-created="{{ $doctorDate }}" data-search="{{ strtolower(($doctor->full_name ?: $doctor->name).' '.($doctor->clinic_name ?? '').' '.($doctor->degree ?: $doctor->speciality).' '.($doctor->mmc_registration_number ?? '').' '.($doctor->city ?? $doctor->location ?? '').' '.($doctor->contact_number ?? $doctor->phone ?? '').' '.($doctor->email ?? '')) }}">
                                <td>{{ $key + 1 }}</td>
                                <td>
                                    <div>
                                        <div class="fw-semibold">Dr. {{ $doctor->full_name ?: $doctor->name }}</div>
                                        <small class="text-muted">{{ $doctor->email ?: 'No email' }}</small>
                                    </div>
                                </td>
                                <td>{{ $doctor->clinic_name ?: '-' }}</td>
                                <td>{{ $doctor->degree ?: $doctor->speciality ?: '-' }}</td>
                                <td>{{ $doctor->mmc_registration_number ?: '-' }}</td>
                                <td>{{ $doctor->city ?: $doctor->location ?: '-' }}</td>
                                <td>{{ $doctor->contact_number ?: $doctor->phone ?: '-' }}</td>
                                <td>{{ optional($doctor->created_at)->format('d-m-Y h:i A') ?: '-' }}</td>
                                <td>
                                    <span class="badge {{ ($doctor->status ?? 'pending') === 'approved' ? 'bg-success' : 'bg-danger' }}">
                                        {{ ($doctor->status ?? 'pending') === 'approved' ? 'Approved' : 'Unapproved' }}
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('doctor.toggle_approval', $doctor) }}" class="doctor-approval-form d-inline-flex align-items-center gap-2" data-doctor="{{ $doctor->full_name ?: $doctor->name }}">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ ($doctor->status ?? 'pending') === 'approved' ? 'pending' : 'approved' }}">
                                        <div class="form-check form-switch mb-0">
                                            <input
                                                class="form-check-input doctor-approval-toggle"
                                                type="checkbox"
                                                role="switch"
                                                {{ ($doctor->status ?? 'pending') === 'approved' ? 'checked' : '' }}
                                            >
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <span class="badge {{ ($doctor->is_active_for_appointments ?? false) ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ ($doctor->is_active_for_appointments ?? false) ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('doctor.show', $doctor) }}" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted">No doctors found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-transparent border-0 pt-4 pb-0">
            <h5 class="mb-3">Live Location</h5>
        </div>
        <div class="card-body pt-1">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Availability</th>
                            <th>State</th>
                            <th>District</th>
                            <th>Taluka</th>
                            <th>City</th>
                            <th>Village</th>
                            <th>Latitude</th>
                            <th>Longitude</th>
                            <th>Updated At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($liveLocations as $idx => $doctor)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td class="fw-semibold">Dr. {{ $doctor->full_name ?: $doctor->name }}</td>
                                <td>{{ $doctor->contact_number ?: $doctor->phone ?: '-' }}</td>
                                <td>
                                    <span class="badge {{ ($doctor->is_active_for_appointments ?? false) ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ ($doctor->is_active_for_appointments ?? false) ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ $doctor->state ?: '-' }}</td>
                                <td>{{ $doctor->district ?: '-' }}</td>
                                <td>{{ $doctor->taluka ?: '-' }}</td>
                                <td>{{ $doctor->city ?: '-' }}</td>
                                <td>{{ $doctor->village ?: '-' }}</td>
                                <td>{{ $doctor->latitude !== null ? number_format((float) $doctor->latitude, 6) : '-' }}</td>
                                <td>{{ $doctor->longitude !== null ? number_format((float) $doctor->longitude, 6) : '-' }}</td>
                                <td>{{ optional($doctor->last_live_location_at ?: $doctor->updated_at)->format('d-m-Y h:i A') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted">No live location data yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="position-fixed top-0 end-0 p-3 doctor-toast-wrap" style="z-index: 1080;">
    <div id="doctorApprovalToast" class="toast border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="false">
        <div class="toast-body">
            <div class="fw-semibold mb-2">Confirm Status Change</div>
            <div class="text-muted mb-3" id="doctorApprovalToastMessage">Are you sure you want to update this doctor status?</div>
            <div class="mt-2 pt-2 border-top d-flex gap-2">
                <button type="button" class="btn btn-success btn-sm" id="doctorApprovalConfirmBtn">Confirm</button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="toast">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .avatar-sm {
        width: 40px;
        height: 40px;
    }
    .avatar-md {
        width: 52px;
        height: 52px;
    }
    .doctor-stat-card {
        overflow: hidden;
        position: relative;
    }
    .doctor-stat-card::before {
        content: '';
        position: absolute;
        inset: 0;
        opacity: 0.08;
    }
    .doctor-stat-card--blue::before { background: #3b82f6; }
    .doctor-stat-card--green::before { background: #10b981; }
    .doctor-stat-card--amber::before { background: #f59e0b; }
    .doctor-stat-card--cyan::before { background: #06b6d4; }
    .doctor-stat-card .card-body {
        position: relative;
        z-index: 1;
    }
    .doctor-stat-label {
        color: #6b7280;
    }
    .doctor-stat-icon {
        background: rgba(255,255,255,0.88);
    }
    .doctor-stat-card--blue .doctor-stat-icon,
    .doctor-stat-card--blue h3,
    .doctor-stat-card--blue i { color: #2563eb; }
    .doctor-stat-card--green .doctor-stat-icon,
    .doctor-stat-card--green h3,
    .doctor-stat-card--green i { color: #059669; }
    .doctor-stat-card--amber .doctor-stat-icon,
    .doctor-stat-card--amber h3,
    .doctor-stat-card--amber i { color: #d97706; }
    .doctor-stat-card--cyan .doctor-stat-icon,
    .doctor-stat-card--cyan h3,
    .doctor-stat-card--cyan i { color: #0891b2; }
    .doctor-toolbar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: nowrap;
    }
    .doctor-toolbar .form-control {
        height: 38px;
        border-radius: 10px;
    }
    .doctor-search-input {
        width: 220px;
    }
    .doctor-date-group {
        width: 260px;
    }
    .doctor-date-group .form-control {
        border-radius: 0;
    }
    .doctor-date-group .form-control:first-child {
        border-top-left-radius: 10px;
        border-bottom-left-radius: 10px;
    }
    .doctor-date-group .form-control:last-child {
        border-top-right-radius: 10px;
        border-bottom-right-radius: 10px;
    }
    .doctor-date-group .input-group-text {
        height: 38px;
        background: #fff;
        color: #6b7280;
        font-weight: 600;
    }
    .doctor-filter-btn {
        min-width: 102px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-color: #198754;
        color: #198754;
        font-weight: 600;
        padding: 0 12px;
    }
    .btn-check:checked + .doctor-filter-btn,
    .doctor-filter-btn:hover,
    .doctor-filter-btn:focus {
        background-color: #198754;
        border-color: #198754;
        color: #fff;
    }
    .doctor-action-btn {
        width: 40px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        flex-shrink: 0;
    }
    .doctor-add-btn {
        height: 38px;
        padding: 0 14px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        white-space: nowrap;
        flex-shrink: 0;
        background-color: #198754;
        border-color: #198754;
        color: #fff;
    }
    .doctor-add-btn:hover,
    .doctor-add-btn:focus {
        background-color: #157347;
        border-color: #157347;
        color: #fff;
    }
    .doctor-approval-toggle {
        width: 2.7rem !important;
        height: 1.35rem;
        cursor: pointer;
    }
    .doctor-approval-toggle:checked {
        background-color: #198754;
        border-color: #198754;
    }
    .doctor-approval-toggle:focus {
        border-color: #198754;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.18);
    }
    .doctor-toast-wrap {
        max-width: 360px;
    }
    #doctorApprovalToast {
        background: #fff;
        border-radius: 16px;
    }
    @media (max-width: 1399.98px) {
        .doctor-toolbar {
            width: 100%;
            overflow-x: auto;
            padding-bottom: 4px;
        }
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/doctor_app/index.js') }}"></script>
@endpush

