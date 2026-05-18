@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row mb-4 mt-2">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">{{ $title }}</h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <input type="text" id="healthSearch" class="form-control" placeholder="Search DMI..." style="width:220px;">
                <div class="input-group" style="width:260px;">
                    <input type="date" id="startDate" class="form-control">
                    <span class="input-group-text">to</span>
                    <input type="date" id="endDate" class="form-control">
                </div>
                <button type="button" class="btn btn-light border" onclick="exportTableToPdf('healthTableExport', '{{ $title }}')" title="Download PDF"><i class="fa-solid fa-file-pdf text-danger"></i></button>
                <button type="button" class="btn btn-light border" onclick="exportTableToExcel('healthTableExport', 'dmi-records')" title="Download Excel"><i class="fa-solid fa-file-excel text-success"></i></button>
            </div>
        </div>
    </div>

    <div class="card mt-2"><div class="card-body pt-2"><div class="table-responsive"><table class="table mb-0" id="healthTableExport"><thead class="table-light"><tr><th>#</th><th>Farmer</th><th>Animal</th><th>Tag</th><th>Body Weight</th><th>Total Milk</th><th>Required DMI</th><th>Actual DMI</th><th>Alert</th><th>Date</th></tr></thead><tbody>@forelse($rows as $key => $row)<tr class="health-row" data-search="{{ strtolower(trim(($row->farmer->first_name ?? '').' '.($row->farmer->last_name ?? '').' '.($row->animal->animal_name ?? '').' '.($row->animal->tag_number ?? '').' '.($row->alert_status ?? ''))) }}" data-date="{{ optional($row->date)->format('Y-m-d') }}"><td>{{ $key + 1 }}</td><td>{{ trim(($row->farmer->first_name ?? '').' '.($row->farmer->last_name ?? '')) ?: '-' }}</td><td>{{ $row->animal->animal_name ?? '-' }}</td><td>{{ $row->animal->tag_number ?? '-' }}</td><td>{{ $row->body_weight }} Kg</td><td>{{ $row->total_milk }} L</td><td>{{ $row->required_dmi }} Kg</td><td>{{ $row->actual_dmi }} Kg</td><td><span class="badge {{ $row->alert_status === 'Balanced' || $row->alert_status === 'Auto Calculated' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">{{ $row->alert_status }}</span></td><td>{{ optional($row->date)->format('d-m-Y') }}</td></tr>@empty<tr><td colspan="10" class="text-center text-muted">No DMI records found</td></tr>@endforelse</tbody></table></div></div></div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/health/index.js') }}"></script>
@endpush
