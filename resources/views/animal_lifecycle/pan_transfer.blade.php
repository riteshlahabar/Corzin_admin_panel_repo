@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4 mt-2">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">Pan Transfer</h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <input type="text" id="lifecycleSearch" class="form-control" placeholder="Search animal..." style="width:220px;">
                <div class="input-group" style="width:260px;">
                    <input type="date" id="startDate" class="form-control">
                    <span class="input-group-text">to</span>
                    <input type="date" id="endDate" class="form-control">
                </div>
                <button type="button" class="btn btn-light border" onclick="exportTableToPdf('lifecycleTableExport', 'Pan Transfer')" title="Download PDF">
                    <i class="fa-solid fa-file-pdf text-danger"></i>
                </button>
                <button type="button" class="btn btn-light border" onclick="exportTableToExcel('lifecycleTableExport', 'pan-transfer')" title="Download Excel">
                    <i class="fa-solid fa-file-excel text-success"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="card mt-2">
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table mb-0" id="lifecycleTableExport">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Farmer</th>
                            <th>Animal Name</th>
                            <th>Tag Number</th>
                            <th>From Pan</th>
                            <th>To Pan</th>
                            <th>Notes</th>
                            <th>Changed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $key => $item)
                        <tr class="lifecycle-row"
                            data-search="{{ strtolower(trim(($item->animal->farmer->first_name ?? '').' '.($item->animal->farmer->last_name ?? '').' '.($item->animal->animal_name ?? '').' '.($item->animal->tag_number ?? '').' '.($item->fromAnimalType->name ?? '').' '.($item->toAnimalType->name ?? ''))) }}"
                            data-date="{{ optional($item->changed_at)->format('Y-m-d') }}">
                            <td>{{ $key + 1 }}</td>
                            <td>{{ trim(($item->animal->farmer->first_name ?? '').' '.($item->animal->farmer->last_name ?? '')) ?: '-' }}</td>
                            <td>{{ $item->animal->animal_name ?? '-' }}</td>
                            <td>{{ $item->animal->tag_number ?? '-' }}</td>
                            <td>{{ $item->fromAnimalType->name ?? '-' }}</td>
                            <td>{{ $item->toAnimalType->name ?? '-' }}</td>
                            <td>{{ $item->notes ?: '-' }}</td>
                            <td>{{ optional($item->changed_at)->format('d-m-Y H:i') ?: '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No pan transfer records found</td>
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
<script src="{{ asset('js/animal_lifecycle/index.js') }}"></script>
@endpush
