@extends('layouts.app')
@section('title', 'Referred Doctor')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 mb-3">
        <h4 class="mb-0 text-dark">Referred Doctor</h4>
    </div>

    <div class="row g-3 mb-4">

    <!-- Total Referred -->
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-lg h-100 overflow-hidden"
             style="background: linear-gradient(135deg, #4e73df, #224abe); border-radius:16px;">
            <div class="card-body position-relative text-white p-4">
                
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="mb-2 text-white-50 fw-semibold">Total Referred</p>
                        <h2 class="fw-bold mb-0">
                            {{ number_format($summary['total_referred'] ?? 0) }}
                        </h2>
                    </div>

                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:60px;height:60px;background:rgba(255,255,255,0.15);">
                        <i class="las la-user-md" style="font-size:30px;"></i>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Approved -->
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-lg h-100 overflow-hidden"
             style="background: linear-gradient(135deg, #1cc88a, #13855c); border-radius:16px;">
            <div class="card-body position-relative text-white p-4">

                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="mb-2 text-white-50 fw-semibold">Approved Referred</p>
                        <h2 class="fw-bold mb-0">
                            {{ number_format($summary['approved_referred'] ?? 0) }}
                        </h2>
                    </div>

                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:60px;height:60px;background:rgba(255,255,255,0.15);">
                        <i class="las la-check-circle" style="font-size:30px;"></i>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Pending -->
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-lg h-100 overflow-hidden"
             style="background: linear-gradient(135deg, #f6c23e, #dda20a); border-radius:16px;">
            <div class="card-body position-relative text-white p-4">

                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="mb-2 text-white-50 fw-semibold">Pending Referred</p>
                        <h2 class="fw-bold mb-0">
                            {{ number_format($summary['pending_referred'] ?? 0) }}
                        </h2>
                    </div>

                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:60px;height:60px;background:rgba(255,255,255,0.15);">
                        <i class="las la-clock" style="font-size:30px;"></i>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Points -->
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-lg h-100 overflow-hidden"
             style="background: linear-gradient(135deg, #9b59b6, #6f42c1); border-radius:16px;">
            <div class="card-body position-relative text-white p-4">

                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="mb-2 text-white-50 fw-semibold">Total Points Distributed</p>
                        <h2 class="fw-bold mb-0">
                            {{ number_format($summary['total_points_distributed'] ?? 0) }}
                        </h2>
                    </div>

                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:60px;height:60px;background:rgba(255,255,255,0.15);">
                        <i class="las la-gift" style="font-size:30px;"></i>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 pb-2">
            <h5 class="mb-0">Referral Entries</h5>
        </div>
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Referrer Doctor</th>
                            <th>Referrer Code</th>
                            <th>Referred Doctor</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Reward</th>
                            <th>Registered On</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($referredDoctors as $index => $doctor)
                            @php
                                $referrer = $doctor->referredBy;
                                $referrerName = $referrer
                                    ? trim(($referrer->first_name ?? '').' '.($referrer->last_name ?? ''))
                                    : '-';
                            @endphp
                            <tr>
                                <td>{{ ($referredDoctors->firstItem() ?? 0) + $index }}</td>
                                <td>{{ $referrerName !== '' ? $referrerName : '-' }}</td>
                                <td><span class="badge bg-light text-dark">{{ $referrer->referral_code ?? '-' }}</span></td>
                                <td>
                                    <div class="fw-semibold">{{ $doctor->full_name ?: '-' }}</div>
                                    <small class="text-muted">{{ $doctor->email ?: '-' }}</small>
                                </td>
                                <td>{{ $doctor->contact_number ?: '-' }}</td>
                                <td>
                                    <span class="badge {{ ($doctor->status ?? 'pending') === 'approved' ? 'bg-success' : 'bg-warning text-dark' }}">
                                        {{ ($doctor->status ?? 'pending') === 'approved' ? 'Approved' : 'Pending' }}
                                    </span>
                                </td>
                                <td>
                                    @if($doctor->referral_reward_granted_at)
                                        <span class="badge bg-success-subtle text-success">Granted</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Pending</span>
                                    @endif
                                </td>
                                <td>{{ optional($doctor->created_at)->format('d-m-Y h:i A') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No referred doctor records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($referredDoctors, 'links'))
                <div class="mt-3">
                    {{ $referredDoctors->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .referral-card--blue { border-left: 4px solid #0d6efd; }
    .referral-card--green { border-left: 4px solid #198754; }
    .referral-card--amber { border-left: 4px solid #fd7e14; }
    .referral-card--violet { border-left: 4px solid #6f42c1; }
</style>
@endpush

