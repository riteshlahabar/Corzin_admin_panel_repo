@extends('layouts.app')

@section('content')
@php
    $recordCount = count($rows ?? []);
    $totalRequired = 0;
    $totalActual = 0;
    $needsAttention = 0;

    foreach (($rows ?? []) as $entry) {
        $totalRequired += (float) ($entry->required_dmi ?? 0);
        $totalActual += (float) ($entry->actual_dmi ?? 0);
        $status = strtolower((string) ($entry->alert_status ?? ''));
        if (!str_contains($status, 'balanced') && !str_contains($status, 'auto')) {
            $needsAttention++;
        }
    }

    $avgRequired = $recordCount > 0 ? $totalRequired / $recordCount : 0;
    $avgActual = $recordCount > 0 ? $totalActual / $recordCount : 0;
@endphp

<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row mb-3 mt-2">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">{{ $title }}</h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <input type="text" id="healthSearch" class="form-control" placeholder="Search DMI..." style="width:220px;">
                <div class="input-group" style="width:260px;">
                    <input type="date" id="startDate" class="form-control">
                    <span class="input-group-text">to</span>
                    <input type="date" id="endDate" class="form-control">
                </div>
                <button type="button" class="btn btn-light border" onclick="exportTableToPdf('healthTableExport', '{{ $title }}')" title="Download PDF">
                    <i class="fa-solid fa-file-pdf text-danger"></i>
                </button>
                <button type="button" class="btn btn-light border" onclick="exportTableToExcel('healthTableExport', 'dmi-records')" title="Download Excel">
                    <i class="fa-solid fa-file-excel text-success"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1">Total Records</p>
                    <h4 class="mb-0">{{ $recordCount }}</h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1">Avg Required DMI</p>
                    <h4 class="mb-0">{{ number_format($avgRequired, 2) }} <small class="text-muted fs-6">Kg</small></h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1">Avg Actual DMI</p>
                    <h4 class="mb-0">{{ number_format($avgActual, 2) }} <small class="text-muted fs-6">Kg</small></h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1">Needs Attention</p>
                    <h4 class="mb-0">{{ $needsAttention }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="healthTableExport">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Farmer</th>
                            <th>Animal</th>
                            <th>Tag</th>
                            <th>Body Weight</th>
                            <th>Total Milk</th>
                            <th>Required DMI</th>
                            <th>Actual DMI</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $key => $row)
                            @php
                                $searchText = strtolower(trim(
                                    ($row->farmer->first_name ?? '') . ' ' .
                                    ($row->farmer->last_name ?? '') . ' ' .
                                    ($row->animal->animal_name ?? '') . ' ' .
                                    ($row->animal->tag_number ?? '') . ' ' .
                                    ($row->alert_status ?? '')
                                ));
                                $status = strtolower((string) ($row->alert_status ?? ''));
                                $isBalanced = str_contains($status, 'balanced') || str_contains($status, 'auto');
                            @endphp
                            <tr class="health-row" data-search="{{ $searchText }}" data-date="{{ optional($row->date)->format('Y-m-d') }}">
                                <td>{{ $key + 1 }}</td>
                                <td>{{ trim(($row->farmer->first_name ?? '') . ' ' . ($row->farmer->last_name ?? '')) ?: '-' }}</td>
                                <td>{{ $row->animal->animal_name ?? '-' }}</td>
                                <td>{{ $row->animal->tag_number ?? '-' }}</td>
                                <td>{{ $row->body_weight }} Kg</td>
                                <td>{{ $row->total_milk }} L</td>
                                <td>{{ $row->required_dmi }} Kg</td>
                                <td>{{ $row->actual_dmi }} Kg</td>
                                <td>
                                    <span class="badge {{ $isBalanced ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                        {{ $row->alert_status }}
                                    </span>
                                </td>
                                <td>{{ optional($row->date)->format('d-m-Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No DMI records found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/health/index.js') }}"></script>
@endpush
