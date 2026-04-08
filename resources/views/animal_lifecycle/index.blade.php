@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4 mt-2">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">{{ $title }}</h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <input type="text" id="lifecycleSearch" class="form-control" placeholder="Search animal..." style="width:220px;">
                <div class="input-group" style="width:260px;">
                    <input type="date" id="startDate" class="form-control">
                    <span class="input-group-text">to</span>
                    <input type="date" id="endDate" class="form-control">
                </div>
                <button type="button" class="btn btn-light border" onclick="exportTableToPdf('lifecycleTableExport', '{{ $title }}')" title="Download PDF">
                    <i class="fa-solid fa-file-pdf text-danger"></i>
                </button>
                <button type="button" class="btn btn-light border" onclick="exportTableToExcel('lifecycleTableExport', '{{ strtolower(str_replace(' ', '-', $title)) }}')" title="Download Excel">
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
                            <th>Unique ID</th>
                            <th>Pan</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Weight</th>
                            <th>{{ $section === 'sold' ? 'Sold At' : ($section === 'death' ? 'Death At' : 'Created At') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $key => $animal)
                        @php
                            $dateValue = $section === 'sold' ? optional($animal->sold_at)->format('Y-m-d') : ($section === 'death' ? optional($animal->death_at)->format('Y-m-d') : optional($animal->created_at)->format('Y-m-d'));
                            $displayDate = $section === 'sold' ? optional($animal->sold_at)->format('d-m-Y H:i') : ($section === 'death' ? optional($animal->death_at)->format('d-m-Y H:i') : optional($animal->created_at)->format('d-m-Y'));
                        @endphp
                        <tr class="lifecycle-row"
                            data-search="{{ strtolower(trim(($animal->farmer->first_name ?? '').' '.($animal->farmer->last_name ?? '').' '.($animal->animal_name ?? '').' '.($animal->tag_number ?? '').' '.($animal->unique_id ?? '').' '.($animal->animalType->name ?? ''))) }}"
                            data-date="{{ $dateValue }}">
                            <td>{{ $key + 1 }}</td>
                            <td>{{ trim(($animal->farmer->first_name ?? '').' '.($animal->farmer->last_name ?? '')) ?: '-' }}</td>
                            <td>{{ $animal->animal_name ?: '-' }}</td>
                            <td>{{ $animal->tag_number ?: '-' }}</td>
                            <td>{{ $animal->unique_id ?: '-' }}</td>
                            <td>{{ $animal->animalType->name ?? '-' }}</td>
                            <td>{{ $animal->calculated_age ?: '-' }}</td>
                            <td>{{ $animal->gender ?: '-' }}</td>
                            <td>{{ $animal->weight ? $animal->weight.' kg' : '-' }}</td>
                            <td>{{ $displayDate ?: '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">No records found</td>
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
