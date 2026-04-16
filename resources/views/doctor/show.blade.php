@extends('layouts.app')
@section('title', 'Doctor Details')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar-xl bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center">
                    <span class="display-6 fw-semibold text-primary">{{ strtoupper(substr($doctor->first_name ?: ($doctor->name ?? 'D'), 0, 1)) }}</span>
                </div>
                <div>
                    <span class="badge {{ ($doctor->status ?? 'pending') === 'approved' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} mb-2">{{ ucfirst($doctor->status ?? 'pending') }}</span>
                    <h4 class="page-title mb-1">{{ $doctor->full_name ?: $doctor->name }}</h4>
                    <p class="text-muted mb-0">{{ $doctor->degree ?: $doctor->speciality ?: '-' }}</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('doctor.index') }}" class="btn btn-light">Back</a>
                <form method="POST" action="{{ route('doctor.toggle_approval', $doctor) }}" onsubmit="return confirm('{{ ($doctor->status ?? 'pending') === 'approved' ? 'Mark this doctor as unapproved?' : 'Approve this doctor?' }}')">
                    @csrf
                    <input type="hidden" name="status" value="{{ ($doctor->status ?? 'pending') === 'approved' ? 'pending' : 'approved' }}">
                    <button type="submit" class="btn {{ ($doctor->status ?? 'pending') === 'approved' ? 'btn-success' : 'btn-outline-success' }}">
                        {{ ($doctor->status ?? 'pending') === 'approved' ? 'Approved' : 'Approve Doctor' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Doctor Profile</h5>
                    <table class="table table-sm align-middle mb-0">
                        <tr><th>Contact</th><td>{{ $doctor->contact_number ?: '-' }}</td></tr>
                        <tr><th>Email</th><td>{{ $doctor->email ?: '-' }}</td></tr>
                        <tr><th>Clinic Name</th><td>{{ $doctor->clinic_name ?: '-' }}</td></tr>
                        <tr><th>Aadhar Number</th><td>{{ $doctor->adhar_number ?: '-' }}</td></tr>
                        <tr><th>PAN Number</th><td>{{ $doctor->pan_number ?: '-' }}</td></tr>
                        <tr><th>MMC</th><td>{{ $doctor->mmc_registration_number ?: '-' }}</td></tr>
                        <tr><th>Clinic Reg</th><td>{{ $doctor->clinic_registration_number ?: '-' }}</td></tr>
                        <tr><th>Clinic Address</th><td>{{ $doctor->clinic_address ?: '-' }}</td></tr>
                        <tr><th>Location</th><td>{{ collect([$doctor->village, $doctor->city, $doctor->taluka, $doctor->district, $doctor->state, $doctor->pincode])->filter()->join(', ') ?: '-' }}</td></tr>
                        <tr><th>Status</th><td><span class="badge {{ ($doctor->status ?? 'pending') === 'approved' ? 'bg-success' : 'bg-warning text-dark' }}">{{ ucfirst($doctor->status ?? 'pending') }}</span></td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Documents</h5>
                        <small class="text-muted">Single column scrollable preview</small>
                    </div>
                    <div style="max-height: 75vh; overflow-y: auto;">
                        @php
                            $documents = [
                                'Doctor Photo' => $doctor->doctorPhotoUrl(),
                                'Aadhar Front Document' => $doctor->documents()['adhar_document_front']
                                    ?? $doctor->documents()['adhar_document']
                                    ?? null,
                                'Aadhar Back Document' => $doctor->documents()['adhar_document_back'] ?? null,
                                'PAN Document' => $doctor->documents()['pan_document'] ?? null,
                                'MMC Document' => $doctor->documents()['mmc_document'] ?? null,
                                'Clinic Registration Document' => $doctor->documents()['clinic_registration_document'] ?? null,
                            ];
                        @endphp

                        @foreach($documents as $label => $url)
                            <div class="border rounded-3 p-3 mb-3 bg-light-subtle">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <strong>{{ $label }}</strong>
                                    @if($url)
                                        <a href="{{ $url }}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                    @endif
                                </div>
                                @if(!$url)
                                    <p class="text-muted mb-0">Document not available</p>
                                @elseif(\Illuminate\Support\Str::endsWith(strtolower($url), ['.jpg', '.jpeg', '.png', '.webp']))
                                    <img src="{{ $url }}" alt="{{ $label }}" class="img-fluid rounded">
                                @else
                                    <iframe src="{{ $url }}" style="width:100%; height:420px; border:1px solid #dee2e6; border-radius:12px;"></iframe>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .avatar-xl {
        width: 72px;
        height: 72px;
    }
</style>
@endpush

