@extends('layouts.app')
@section('title', 'Doctor Plan')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h4 class="mb-0 text-dark">Doctor Plan</h4>
        <small class="text-muted">Create and manage subscription plans for doctors.</small>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Add New Plan</h5>
                    <form method="POST" action="{{ route('doctor.plan.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Plan Name</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price (Rs)</label>
                            <input type="number" step="0.01" min="0" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}" required>
                            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Duration (Days)</label>
                            <input type="number" min="1" name="duration_days" class="form-control @error('duration_days') is-invalid @enderror" value="{{ old('duration_days', 30) }}" required>
                            @error('duration_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Features</label>
                            <textarea name="features" rows="4" class="form-control @error('features') is-invalid @enderror" placeholder="Enter plan features">{{ old('features') }}</textarea>
                            @error('features')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="isActiveDoctorPlan" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActiveDoctorPlan">Active Plan</label>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Save Plan</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Plan</th>
                                    <th>Price</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Features</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($plans as $plan)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $plan->name }}</td>
                                        <td>Rs {{ number_format((float) $plan->price, 2) }}</td>
                                        <td>{{ $plan->duration_days }} days</td>
                                        <td>
                                            <span class="badge {{ $plan->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                                {{ $plan->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>{{ \Illuminate\Support\Str::limit((string) $plan->features, 60, '...') ?: '-' }}</td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editDoctorPlan{{ $plan->id }}">
                                                Edit
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No doctor plans found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach($plans as $plan)
        <div class="modal fade" id="editDoctorPlan{{ $plan->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Doctor Plan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('doctor.plan.update', $plan) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Plan Name</label>
                                    <input type="text" name="name" class="form-control" value="{{ $plan->name }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Price (Rs)</label>
                                    <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ $plan->price }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Duration (Days)</label>
                                    <input type="number" min="1" name="duration_days" class="form-control" value="{{ $plan->duration_days }}" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Features</label>
                                    <textarea name="features" rows="4" class="form-control">{{ $plan->features }}</textarea>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="doctorPlanActive{{ $plan->id }}" name="is_active" value="1" {{ $plan->is_active ? 'checked' : '' }}>
                                        <label class="form-check-label" for="doctorPlanActive{{ $plan->id }}">Active Plan</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Update Plan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection

