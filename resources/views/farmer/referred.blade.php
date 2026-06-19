@extends('layouts.app')
@section('title', 'Refer & Earn')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 mb-3">
        <div>
            <h4 class="mb-1 text-dark">Refer & Earn</h4>
            <p class="text-muted mb-0">Track farmers referred by other farmers and their reward eligibility.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-lg h-100 overflow-hidden" style="background: linear-gradient(135deg, #4e73df, #224abe); border-radius:16px;">
                <div class="card-body text-white p-4 d-flex justify-content-between align-items-start">
                    <div>
                        <p class="mb-2 text-white-50 fw-semibold">Total Referred Farmers</p>
                        <h2 class="fw-bold mb-0">{{ number_format($summary['total_referred'] ?? 0) }}</h2>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:60px;height:60px;background:rgba(255,255,255,0.15);">
                        <i class="las la-users" style="font-size:30px;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-lg h-100 overflow-hidden" style="background: linear-gradient(135deg, #1cc88a, #13855c); border-radius:16px;">
                <div class="card-body text-white p-4 d-flex justify-content-between align-items-start">
                    <div>
                        <p class="mb-2 text-white-50 fw-semibold">Active Subscriptions</p>
                        <h2 class="fw-bold mb-0">{{ number_format($summary['active_subscription'] ?? 0) }}</h2>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:60px;height:60px;background:rgba(255,255,255,0.15);">
                        <i class="las la-check-circle" style="font-size:30px;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-lg h-100 overflow-hidden" style="background: linear-gradient(135deg, #f6c23e, #dda20a); border-radius:16px;">
                <div class="card-body text-white p-4 d-flex justify-content-between align-items-start">
                    <div>
                        <p class="mb-2 text-white-50 fw-semibold">Reward Pending</p>
                        <h2 class="fw-bold mb-0">{{ number_format($summary['reward_pending'] ?? 0) }}</h2>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:60px;height:60px;background:rgba(255,255,255,0.15);">
                        <i class="las la-clock" style="font-size:30px;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-lg h-100 overflow-hidden" style="background: linear-gradient(135deg, #9b59b6, #6f42c1); border-radius:16px;">
                <div class="card-body text-white p-4 d-flex justify-content-between align-items-start">
                    <div>
                        <p class="mb-2 text-white-50 fw-semibold">Farmer Points</p>
                        <h2 class="fw-bold mb-0">{{ number_format($summary['total_points_distributed'] ?? 0) }}</h2>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:60px;height:60px;background:rgba(255,255,255,0.15);">
                        <i class="las la-gift" style="font-size:30px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 pb-2">
            <h5 class="mb-0">Farmer Referral Entries</h5>
        </div>
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Referrer Farmer</th>
                            <th>Referral Code</th>
                            <th>Joined Farmer</th>
                            <th>Subscription</th>
                            <th>Reward</th>
                            <th>Registered On</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($referredFarmers as $index => $farmer)
                            @php
                                $referrer = $farmer->referredByFarmer;
                                $referrerName = $referrer ? trim(($referrer->first_name ?? '').' '.($referrer->last_name ?? '')) : '-';
                                $farmerName = trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? ''));
                                $subscription = $farmer->subscription;
                                $subscriptionStatus = strtolower((string) optional($subscription)->status);
                                $eligibleDate = $farmer->created_at ? $farmer->created_at->copy()->addMonth() : null;
                            @endphp
                            <tr>
                                <td>{{ ($referredFarmers->firstItem() ?? 0) + $index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $referrerName !== '' ? $referrerName : '-' }}</div>
                                    <small class="text-muted">{{ optional($referrer)->mobile ?: '-' }}</small>
                                </td>
                                <td><span class="badge bg-light text-dark">{{ $farmer->farmer_referral_code ?: optional($referrer)->referral_code ?: '-' }}</span></td>
                                <td>
                                    <div class="fw-semibold">{{ $farmerName !== '' ? $farmerName : '-' }}</div>
                                    <small class="text-muted">{{ $farmer->mobile ?: '-' }}</small>
                                </td>
                                <td>
                                    @if($subscription)
                                        <span class="badge {{ $subscriptionStatus === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ ucfirst($subscriptionStatus ?: 'active') }}
                                        </span>
                                        <div class="small text-muted mt-1">{{ optional($subscription->plan)->name ?: 'Plan' }}</div>
                                    @else
                                        <span class="badge bg-warning text-dark">No Subscription</span>
                                    @endif
                                </td>
                                <td>
                                    @if($farmer->farmer_referral_reward_granted_at)
                                        <span class="badge bg-success-subtle text-success">Granted</span>
                                        <div class="small text-muted mt-1">{{ optional($farmer->farmer_referral_reward_granted_at)->format('d-m-Y') }}</div>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Pending</span>
                                        <div class="small text-muted mt-1">Eligible after {{ $eligibleDate ? $eligibleDate->format('d-m-Y') : '-' }}</div>
                                    @endif
                                </td>
                                <td>{{ optional($farmer->created_at)->format('d-m-Y h:i A') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No farmer referral records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @include('partials.table-pagination', ['paginator' => $referredFarmers])
    </div>
</div>
@endsection
