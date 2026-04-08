@extends('layouts.app')
@section('title', 'Doctor Subscription')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h4 class="mb-0 text-dark">Doctor Subscription</h4>
        <small class="text-muted">Track doctor current plan, due date and renewal suggestion.</small>
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
            <h5 class="mb-3">Assign / Update Doctor Plan</h5>
            <form method="POST" action="{{ route('doctor.subscription.store') }}" class="row g-3">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Doctor</label>
                    <select name="doctor_id" class="form-select @error('doctor_id') is-invalid @enderror" required>
                        <option value="">Select Doctor</option>
                        @foreach($doctorOptions as $doctor)
                            <option value="{{ $doctor->id }}">{{ trim(($doctor->first_name ?? '').' '.($doctor->last_name ?? '')) }} - {{ $doctor->contact_number }}</option>
                        @endforeach
                    </select>
                    @error('doctor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Plan</label>
                    <select name="doctor_plan_id" class="form-select @error('doctor_plan_id') is-invalid @enderror" required>
                        <option value="">Select Plan</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} (Rs {{ number_format((float) $plan->price, 2) }})</option>
                        @endforeach
                    </select>
                    @error('doctor_plan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                    <button type="submit" class="btn btn-success">Save Doctor Subscription</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('doctor.subscription.index') }}" class="row g-2 mb-3">
                <div class="col-md-5">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        class="form-control"
                        placeholder="Search doctor name, phone or email..."
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
                            <th>Doctor</th>
                            <th>Current Plan</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Suggestion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($doctors as $doctor)
                            @php
                                $subscription = $doctor->subscription;
                                $dueDate = optional($subscription)->due_date;
                                $status = strtolower((string) optional($subscription)->status);
                                $isExpired = $dueDate ? $dueDate->lt(now()->startOfDay()) : false;
                                $isExpiringSoon = $dueDate ? $dueDate->between(now()->startOfDay(), now()->copy()->addDays(7)->endOfDay()) : false;
                                if (! $subscription) {
                                    $suggestion = 'Assign a subscription plan to this doctor.';
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
                                <td>{{ $doctors->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ trim(($doctor->first_name ?? '').' '.($doctor->last_name ?? '')) ?: '-' }}</div>
                                    <small class="text-muted">{{ $doctor->contact_number ?: '-' }}</small>
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
                                <td colspan="6" class="text-center text-muted py-4">No doctors found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($doctors->hasPages())
            <div class="card-footer bg-white">
                {{ $doctors->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
