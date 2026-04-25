@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row g-3 mb-4 mt-2">
        <div class="col-md-6 col-lg-3">
            <div class="card bg-warning-subtle">
                <div class="card-body text-center">
                    <h5 class="fw-bold mb-1" style="font-size:18px;">Morning Milk</h5>
                    <h2 class="fw-bold mb-0">{{ number_format($summary['morning'], 1) }} L</h2>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card bg-info-subtle">
                <div class="card-body text-center">
                    <h5 class="fw-bold mb-1" style="font-size:18px;">Afternoon Milk</h5>
                    <h2 class="fw-bold mb-0">{{ number_format($summary['afternoon'], 1) }} L</h2>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card bg-secondary-subtle">
                <div class="card-body text-center">
                    <h5 class="fw-bold mb-1" style="font-size:18px;">Evening Milk</h5>
                    <h2 class="fw-bold mb-0">{{ number_format($summary['evening'], 1) }} L</h2>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card bg-success-subtle">
                <div class="card-body text-center">
                    <h5 class="fw-bold mb-1" style="font-size:18px;">Avg FAT</h5>
                    <h2 class="fw-bold mb-0">{{ number_format($summary['fat'], 1) }} %</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">Milk Production</h4>
            <div class="d-flex align-items-center gap-2 flex-nowrap overflow-auto">
                <div class="btn-group" role="group">
                    <input type="checkbox" class="btn-check shift-filter" id="morningCheck" value="morning" checked>
                    <label class="btn btn-outline-primary active" for="morningCheck">Morning</label>
                    <input type="checkbox" class="btn-check shift-filter" id="afternoonCheck" value="afternoon" checked>
                    <label class="btn btn-outline-primary active" for="afternoonCheck">Afternoon</label>
                    <input type="checkbox" class="btn-check shift-filter" id="eveningCheck" value="evening" checked>
                    <label class="btn btn-outline-primary active" for="eveningCheck">Evening</label>
                </div>
                <input type="text" id="milkSearch" class="form-control" placeholder="Search farmer, animal, dairy..." style="width:300px;">
                <div class="input-group" style="width:260px;">
                    <input type="date" id="startDate" class="form-control">
                    <span class="input-group-text">to</span>
                    <input type="date" id="endDate" class="form-control">
                </div>
                <button type="button" class="btn btn-light border" onclick="exportTableToPdf('milkTableExport', 'Milk Production')" title="Download PDF">
                    <i class="fa-solid fa-file-pdf text-danger"></i>
                </button>
                <button type="button" class="btn btn-light border" onclick="exportTableToExcel('milkTableExport', 'milk-production')" title="Download Excel">
                    <i class="fa-solid fa-file-excel text-success"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="card mt-2">
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table mb-0" id="milkTableExport">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Farmer</th>
                            <th>Dairy</th>
                            <th>Animal</th>
                            <th>Morning (L)</th>
                            <th>Afternoon (L)</th>
                            <th>Evening (L)</th>
                            <th>Total (L)</th>
                            <th>FAT</th>
                            <th>SNF</th>
                            <th>Rate</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($milkProductions as $key => $milk)
                        <tr class="milk-row"
                            data-farmer="{{ strtolower(trim(($milk->animal->farmer->first_name ?? '').' '.($milk->animal->farmer->last_name ?? ''))) }}"
                            data-dairy="{{ strtolower($milk->dairy->dairy_name ?? '') }}"
                            data-search="{{ strtolower(trim(($milk->animal->farmer->first_name ?? '').' '.($milk->animal->farmer->last_name ?? '').' '.($milk->dairy->dairy_name ?? '').' '.($milk->animal->animal_name ?? ''))) }}"
                            data-date="{{ $milk->date }}"
                            data-morning="{{ $milk->morning_milk > 0 ? 1 : 0 }}"
                            data-afternoon="{{ ($milk->afternoon_milk ?? 0) > 0 ? 1 : 0 }}"
                            data-evening="{{ $milk->evening_milk > 0 ? 1 : 0 }}">
                            <td>{{ $key + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($milk->date)->format('d-m-Y') }}</td>
                            <td>{{ trim(($milk->animal->farmer->first_name ?? '').' '.($milk->animal->farmer->last_name ?? '')) ?: '-' }}</td>
                            <td>{{ $milk->dairy->dairy_name ?? '-' }}</td>
                            <td>{{ $milk->animal->animal_name ?? '-' }}</td>
                            <td>{{ $milk->morning_milk }}</td>
                            <td>{{ $milk->afternoon_milk ?? 0 }}</td>
                            <td>{{ $milk->evening_milk }}</td>
                            <td><span class="fw-semibold">{{ $milk->total_milk }}</span></td>
                            <td>{{ $milk->fat ?? '-' }}</td>
                            <td>{{ $milk->snf ?? '-' }}</td>
                            <td>{{ $milk->rate ?? '-' }}</td>
                            <td class="text-end">
                                <a href="#" class="btn btn-sm btn-light border me-1"><i class="las la-pen text-primary fs-18"></i></a>
                                <a href="#" class="btn btn-sm btn-light border"><i class="las la-trash-alt text-danger fs-18"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="13" class="text-center text-muted">No Milk Records Found</td>
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
<script src="{{ asset('js/milk_production/index.js') }}"></script>
@endpush
