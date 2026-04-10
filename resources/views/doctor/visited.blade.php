@extends('layouts.app')
@section('title', 'Visited')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h4 class="mb-0 text-dark">Visited</h4>
        <small class="text-muted">Completed doctor visits are listed here.</small>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('doctor.visited') }}" class="row g-2 mb-3">
                <div class="col-md-9">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        class="form-control"
                        placeholder="Search farmer, animal, concern..."
                    >
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
                            <th>Doctor</th>
                            <th>Farmer</th>
                            <th>Animal</th>
                            <th>Concern</th>
                            <th>On-Site Treatment</th>
                            <th>Completed Date</th>
                            <th>Charges</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($visits as $visit)
                            <tr>
                                <td>{{ $visits->firstItem() + $loop->index }}</td>
                                <td>{{ $visit->doctor->full_name ?: $visit->doctor->name ?: '-' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $visit->farmer_name ?: '-' }}</div>
                                    <small class="text-muted">{{ $visit->farmer_phone ?: '-' }}</small>
                                </td>
                                <td>{{ $visit->animal_name ?: '-' }}</td>
                                <td style="min-width:220px;">{{ $visit->concern ?: '-' }}</td>
                                <td style="min-width:220px;">{{ $visit->onsite_treatment ?: '-' }}</td>
                                <td>{{ optional($visit->completed_at ?: $visit->updated_at)->format('d-m-Y h:i A') ?: '-' }}</td>
                                <td>{{ $visit->charges !== null ? '₹ '.number_format((float) $visit->charges, 2) : '-' }}</td>
                                <td><span class="badge bg-success">Completed</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No visited records found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($visits->hasPages())
            <div class="card-footer bg-white">
                {{ $visits->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
