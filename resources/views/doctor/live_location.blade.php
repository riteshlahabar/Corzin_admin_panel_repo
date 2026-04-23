@extends('layouts.app')
@section('title', 'Doctor Live Location')

@section('content')
<div class="container-fluid">
    <div class="card border-0 shadow-sm mt-2">
        <div class="card-header bg-transparent border-0 pt-4 pb-0">
            <h4 class="mb-3">Live Location</h4>
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
                            <th>Address</th>
                            <th>Last Location</th>
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
                                <td style="min-width: 320px;">
                                    {{ $doctor->live_location_address ?: '-' }}
                                </td>
                                <td>{{ optional($doctor->last_live_location_at ?: $doctor->updated_at)->format('d-m-Y h:i A') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No live location data yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
