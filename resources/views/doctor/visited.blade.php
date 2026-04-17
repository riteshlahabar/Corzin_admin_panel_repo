@extends('layouts.app')
@section('title', 'Visited')

@section('content')
<div class="container-fluid">
    <style>
        .doctor-table {
            font-size: 0.85rem;
        }
        .doctor-table thead th {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            white-space: nowrap;
            padding-top: 0.7rem;
            padding-bottom: 0.7rem;
        }
        .doctor-table tbody td {
            vertical-align: middle;
            padding-top: 0.6rem;
            padding-bottom: 0.6rem;
        }
        .doctor-table .fw-semibold {
            font-size: 0.84rem;
        }
        .doctor-table small {
            font-size: 0.74rem;
        }
    </style>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 mt-4 pt-2">
        <h4 class="mb-0 text-dark">Visited</h4>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form id="visitedSearchForm" method="GET" action="{{ route('doctor.visited') }}" class="row g-2 mb-3">
                <div class="col-md-4 col-lg-3">
                    <input
                        id="visitedSearchInput"
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        class="form-control"
                        placeholder="Search farmer, animal, concern..."
                    >
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 doctor-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Appointment ID</th>
                            <th>Doctor</th>
                            <th>Farmer</th>
                            <th>Animal</th>
                            <th>Concern</th>
                            <th>Medicine</th>
                            <th>On-Site Treatment</th>
                            <th>Completed Date</th>
                            <th>Charges</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($visits as $visit)
                            @php
                                $farmerFullName = trim(implode(' ', array_filter([
                                    optional($visit->farmer)->first_name,
                                    optional($visit->farmer)->middle_name,
                                    optional($visit->farmer)->last_name,
                                ])));

                                $medicineSummary = '-';
                                $treatmentDetails = $visit->treatment_details;
                                if (is_string($treatmentDetails)) {
                                    $decoded = json_decode($treatmentDetails, true);
                                    if (json_last_error() === JSON_ERROR_NONE) {
                                        $treatmentDetails = $decoded;
                                    }
                                }

                                if (is_array($treatmentDetails)) {
                                    $medicineRows = [];
                                    $medicineSource = $treatmentDetails['medicines'] ?? $treatmentDetails;
                                    if (is_array($medicineSource)) {
                                        foreach ($medicineSource as $item) {
                                            if (is_array($item)) {
                                                $name = trim((string) ($item['medicine'] ?? $item['name'] ?? ''));
                                                $qty = trim((string) ($item['total'] ?? $item['tabs'] ?? $item['quantity'] ?? ''));
                                                $line = trim($name.($qty !== '' ? ' ('.$qty.')' : ''));
                                                if ($line !== '') {
                                                    $medicineRows[] = $line;
                                                }
                                            } elseif (is_string($item) && trim($item) !== '') {
                                                $medicineRows[] = trim($item);
                                            }
                                        }
                                    }
                                    if (! empty($medicineRows)) {
                                        $medicineSummary = implode(', ', array_slice($medicineRows, 0, 4));
                                    }
                                } elseif (is_string($treatmentDetails) && trim($treatmentDetails) !== '') {
                                    $medicineSummary = trim($treatmentDetails);
                                }
                            @endphp
                            <tr>
                                <td>{{ $visits->firstItem() + $loop->index }}</td>
                                <td><span class="fw-semibold">{{ $visit->appointment_code }}</span></td>
                                <td>{{ $visit->doctor->full_name ?: $visit->doctor->name ?: '-' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $farmerFullName !== '' ? $farmerFullName : ($visit->farmer_name ?: '-') }}</div>
                                    <small class="text-muted">{{ $visit->farmer_phone ?: '-' }}</small>
                                </td>
                                <td>{{ $visit->animal_name ?: '-' }}</td>
                                <td style="min-width:220px;">{{ $visit->concern ?: '-' }}</td>
                                <td style="min-width:220px;">{{ $medicineSummary }}</td>
                                <td style="min-width:220px;">{{ $visit->onsite_treatment ?: '-' }}</td>
                                <td>{{ optional($visit->completed_at ?: $visit->updated_at)->format('d-m-Y h:i A') ?: '-' }}</td>
                                <td>{{ $visit->charges !== null ? '₹ '.number_format((float) $visit->charges, 2) : '-' }}</td>
                                <td><span class="badge bg-success">Completed</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">No visited records found</td>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('visitedSearchInput');
    const form = document.getElementById('visitedSearchForm');
    if (!input || !form) return;

    let timer = null;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            form.submit();
        }, 350);
    });
});
</script>
@endsection
