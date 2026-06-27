@extends('layouts.app')

@push('styles')
<style>
    .milk-summary-card {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
    }
    .milk-summary-card .card-body h5,
    .milk-summary-card .card-body h2 {
        color: #fff;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row g-3 mb-4 mt-2">
        <div class="col-md-6 col-lg-3">
            <div class="card milk-summary-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <div class="card-body text-center">
                    <h5 class="fw-bold mb-1" style="font-size:18px;">Morning Milk</h5>
                    <h2 class="fw-bold mb-0" id="summaryMorning">{{ number_format($summary['morning'], 1) }} L</h2>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card milk-summary-card" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);">
                <div class="card-body text-center">
                    <h5 class="fw-bold mb-1" style="font-size:18px;">Afternoon Milk</h5>
                    <h2 class="fw-bold mb-0" id="summaryAfternoon">{{ number_format($summary['afternoon'], 1) }} L</h2>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card milk-summary-card" style="background: linear-gradient(135deg, #64748b 0%, #475569 100%);">
                <div class="card-body text-center">
                    <h5 class="fw-bold mb-1" style="font-size:18px;">Evening Milk</h5>
                    <h2 class="fw-bold mb-0" id="summaryEvening">{{ number_format($summary['evening'], 1) }} L</h2>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card milk-summary-card" style="background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);">
                <div class="card-body text-center">
                    <h5 class="fw-bold mb-1" style="font-size:18px;">Avg FAT</h5>
                    <h2 class="fw-bold mb-0" id="summaryFat">{{ number_format($summary['fat'], 1) }} %</h2>
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
                <select id="milkSearchField" class="form-select" style="width:190px;">
                    <option value="all">All Columns</option>
                    <option value="date">Date</option>
                    <option value="farmer">Farmer</option>
                    <option value="dairy">Dairy</option>
                    <option value="animal">Animal</option>
                    <option value="morning-text">Morning (L)</option>
                    <option value="afternoon-text">Afternoon (L)</option>
                    <option value="evening-text">Evening (L)</option>
                    <option value="total">Total (L)</option>
                    <option value="fat">FAT</option>
                    <option value="snf">SNF</option>
                    <option value="rate">Rate</option>
                </select>
                <input type="text" id="milkSearch" class="form-control" placeholder="Search selected field..." style="width:220px;">
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
                @perm('milk_production.add')
                <a href="{{ route('farmer.milk.create') }}" class="btn btn-success">
                    <i class="fa-solid fa-plus me-1"></i> Add Milk
                </a>
                @endperm
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
                            data-animal="{{ strtolower($milk->animal->animal_name ?? '') }}"
                            data-date="{{ strtolower(\Carbon\Carbon::parse($milk->date)->format('d-m-Y')) }}"
                            data-date-raw="{{ $milk->date }}"
                            data-search="{{ strtolower(trim(($milk->animal->farmer->first_name ?? '').' '.($milk->animal->farmer->last_name ?? '').' '.($milk->dairy->dairy_name ?? '').' '.($milk->animal->animal_name ?? '').' '.\Carbon\Carbon::parse($milk->date)->format('d-m-Y').' '.($milk->morning_milk ?? 0).' '.($milk->afternoon_milk ?? 0).' '.($milk->evening_milk ?? 0).' '.($milk->total_milk ?? 0).' '.($milk->fat ?? '').' '.($milk->snf ?? '').' '.($milk->rate ?? ''))) }}"
                            data-morning="{{ $milk->morning_milk > 0 ? 1 : 0 }}"
                            data-afternoon="{{ ($milk->afternoon_milk ?? 0) > 0 ? 1 : 0 }}"
                            data-evening="{{ $milk->evening_milk > 0 ? 1 : 0 }}"
                            data-morning-text="{{ strtolower((string) ($milk->morning_milk ?? 0)) }}"
                            data-afternoon-text="{{ strtolower((string) ($milk->afternoon_milk ?? 0)) }}"
                            data-evening-text="{{ strtolower((string) ($milk->evening_milk ?? 0)) }}"
                            data-total="{{ strtolower((string) ($milk->total_milk ?? 0)) }}"
                            data-fat="{{ strtolower((string) ($milk->fat ?? '')) }}"
                            data-snf="{{ strtolower((string) ($milk->snf ?? '')) }}"
                            data-rate="{{ strtolower((string) ($milk->rate ?? '')) }}"
                            data-morning-value="{{ (float) ($milk->morning_milk ?? 0) }}"
                            data-afternoon-value="{{ (float) ($milk->afternoon_milk ?? 0) }}"
                            data-evening-value="{{ (float) ($milk->evening_milk ?? 0) }}"
                            data-fat-value="{{ $milk->fat !== null ? (float) $milk->fat : '' }}">
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



