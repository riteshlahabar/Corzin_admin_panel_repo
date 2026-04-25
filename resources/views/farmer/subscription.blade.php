@extends('layouts.app')
@section('title', 'Farmer Subscription')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="mt-4 mb-3">
        <h4 class="mb-0 text-dark">Farmer Subscription</h4>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Total Subscriptions</p>
                    <h4 class="mb-0">{{ $summary['total'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Active</p>
                    <h4 class="mb-0 text-success">{{ $summary['active'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Expiring (7 Days)</p>
                    <h4 class="mb-0 text-warning">{{ $summary['expiring_soon'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Expired</p>
                    <h4 class="mb-0 text-danger">{{ $summary['expired'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h5 class="mb-3">Assign / Update Farmer Plan</h5>
            <form method="POST" action="{{ route('farmer.subscription.store') }}" class="row g-3">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Farmer</label>
                    <select name="farmer_id" class="form-select @error('farmer_id') is-invalid @enderror" required>
                        <option value="">Select Farmer</option>
                        @foreach($farmerOptions as $farmer)
                            <option value="{{ $farmer->id }}">{{ trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) }} - {{ $farmer->mobile }}</option>
                        @endforeach
                    </select>
                    @error('farmer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Plan</label>
                    <select name="farmer_plan_id" class="form-select @error('farmer_plan_id') is-invalid @enderror" required>
                        <option value="">Select Plan</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} (Rs {{ number_format((float) $plan->price, 2) }})</option>
                        @endforeach
                    </select>
                    @error('farmer_plan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}">
                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}">
                    @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="">Auto</option>
                        <option value="active">Active</option>
                        <option value="expired">Expired</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Notes (Optional)</label>
                    <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" placeholder="Write any renewal note">{{ old('notes') }}</textarea>
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success">Save Farmer Subscription</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('farmer.subscription.index') }}" class="row g-2 mb-3">
                <div class="col-md-5">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        class="form-control"
                        placeholder="Search farmer name or mobile..."
                    >
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-success">Search</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Farmer</th>
                            <th>Current Plan</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Suggestion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($farmers as $farmer)
                            @php
                                $subscription = $farmer->subscription;
                                $dueDate = optional($subscription)->due_date;
                                $status = strtolower((string) optional($subscription)->status);
                                $isExpired = $dueDate ? $dueDate->lt(now()->startOfDay()) : false;
                                $isExpiringSoon = $dueDate ? $dueDate->between(now()->startOfDay(), now()->copy()->addDays(7)->endOfDay()) : false;
                                if (! $subscription) {
                                    $suggestion = 'Assign a subscription plan to this farmer.';
                                } elseif ($status === 'cancelled') {
                                    $suggestion = 'Subscription cancelled. Assign a new active plan.';
                                } elseif ($isExpired || $status === 'expired') {
                                    $suggestion = 'Plan expired. Renew immediately.';
                                } elseif ($isExpiringSoon) {
                                    $suggestion = 'Plan due soon. Send renewal reminder.';
                                } else {
                                    $suggestion = 'Subscription is healthy.';
                                }
                            @endphp
                            <tr>
                                <td>{{ $farmers->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) ?: '-' }}</div>
                                    <small class="text-muted">{{ $farmer->mobile ?: '-' }}</small>
                                </td>
                                <td>{{ optional(optional($subscription)->plan)->name ?: '-' }}</td>
                                <td>{{ $dueDate ? $dueDate->format('d-m-Y') : '-' }}</td>
                                <td>
                                    <span class="badge {{
                                        ! $subscription ? 'bg-secondary' :
                                        (($status === 'active' && ! $isExpired) ? 'bg-success' :
                                        (($status === 'cancelled') ? 'bg-dark' : 'bg-danger'))
                                    }}">
                                        {{ $subscription ? ucfirst($status ?: 'active') : 'No Plan' }}
                                    </span>
                                </td>
                                <td>{{ $suggestion }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No farmers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($farmers->hasPages())
            <div class="card-footer bg-white">
                {{ $farmers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
