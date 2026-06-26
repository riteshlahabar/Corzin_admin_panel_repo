@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row mb-3 mt-2">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">{{ $title }}</h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <select id="healthSearchField" class="form-select" style="width:190px;">
                    <option value="all">All Columns</option>
                    <option value="farmer">Farmer</option>
                    <option value="animal">Animal</option>
                    <option value="tag">Tag</option>
                    <option value="body-weight">Body Weight</option>
                    <option value="total-milk">Total Milk</option>
                    <option value="required-dmi">Required DMI</option>
                    <option value="actual-dmi">Actual DMI</option>
                    <option value="date-text">Date</option>
                </select>
                <input type="text" id="healthSearch" class="form-control" placeholder="Search selected field..." style="width:220px;">
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
                            <tr class="health-row"
                                data-all="{{ $searchText.' '.strtolower((string) ($row->body_weight ?? '')).' '.strtolower((string) ($row->total_milk ?? '')).' '.strtolower((string) ($row->required_dmi ?? '')).' '.strtolower((string) ($row->actual_dmi ?? '')).' '.strtolower(optional($row->date)->format('d-m-Y') ?? '') }}"
                                data-farmer="{{ strtolower(trim(($row->farmer->first_name ?? '') . ' ' . ($row->farmer->last_name ?? ''))) }}"
                                data-animal="{{ strtolower($row->animal->animal_name ?? '') }}"
                                data-tag="{{ strtolower($row->animal->tag_number ?? '') }}"
                                data-body-weight="{{ strtolower((string) ($row->body_weight ?? '')) }}"
                                data-total-milk="{{ strtolower((string) ($row->total_milk ?? '')) }}"
                                data-required-dmi="{{ strtolower((string) ($row->required_dmi ?? '')) }}"
                                data-actual-dmi="{{ strtolower((string) ($row->actual_dmi ?? '')) }}"
                                data-date-text="{{ strtolower(optional($row->date)->format('d-m-Y') ?? '') }}"
                                data-date="{{ optional($row->date)->format('Y-m-d') }}">
                                <td>{{ $key + 1 }}</td>
                                <td>{{ trim(($row->farmer->first_name ?? '') . ' ' . ($row->farmer->last_name ?? '')) ?: '-' }}</td>
                                <td>{{ $row->animal->animal_name ?? '-' }}</td>
                                <td>{{ $row->animal->tag_number ?? '-' }}</td>
                                <td>{{ $row->body_weight }} Kg</td>
                                <td>{{ $row->total_milk }} L</td>
                                <td>{{ $row->required_dmi }} Kg</td>
                                <td>{{ $row->actual_dmi }} Kg</td>
                                <td>{{ optional($row->date)->format('d-m-Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No DMI records found</td>
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
