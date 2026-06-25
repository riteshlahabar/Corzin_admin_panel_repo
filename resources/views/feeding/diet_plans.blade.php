@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-4 mt-2">
        <div class="col-md-4">
            <div class="card bg-primary-subtle border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">Total Diet Plans</p>
                    <h3 class="mb-0">{{ $summary['total'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success-subtle border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">Active Plans</p>
                    <h3 class="mb-0">{{ $summary['active'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning-subtle border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">Package Quantity</p>
                    <h3 class="mb-0">{{ number_format($summary['planned_quantity'], 2) }} Kg</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">Diet Plan List</h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <select id="dietPlanSearchField" class="form-select" style="width:190px;">
                    <option value="all">All Columns</option>
                    <option value="farmer">Farmer</option>
                    <option value="target">Target</option>
                    <option value="diet-plan-name">Diet Plan Name</option>
                    <option value="feed-type">Feed Type</option>
                    <option value="package-quantity">Package Quantity</option>
                    <option value="planned-dry-matter">Planned Dry Matter</option>
                    <option value="target-dmi">Required DMI</option>
                    <option value="milk-production">Milk Production</option>
                    <option value="body-weight">Body Weight</option>
                    <option value="status">Status</option>
                    <option value="date-text">Date</option>
                </select>
                <input type="text" id="dietPlanSearch" class="form-control" placeholder="Search selected field..." style="width:220px;">
                <div class="input-group" style="width:260px;">
                    <input type="date" id="startDate" class="form-control">
                    <span class="input-group-text">to</span>
                    <input type="date" id="endDate" class="form-control">
                </div>
                @perm('diet_plan.export')
                <button type="button" class="btn btn-light border" onclick="exportTableToPdf('dietPlanTableExport', 'Diet Plan List')" title="Download PDF">
                    <i class="fa-solid fa-file-pdf text-danger"></i>
                </button>
                <button type="button" class="btn btn-light border" onclick="exportTableToExcel('dietPlanTableExport', 'diet-plan-list')" title="Download Excel">
                    <i class="fa-solid fa-file-excel text-success"></i>
                </button>
                @endperm
            </div>
        </div>
    </div>

    <div class="card mt-2">
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table mb-0" id="dietPlanTableExport">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Farmer</th>
                            <th>Target</th>
                            <th>Diet Plan Name</th>
                            <th>Feed Type</th>
                            <th>Package Quantity</th>
                            <th>Planned Dry Matter</th>
                            <th>Required DMI</th>
                            <th>Milk Production</th>
                            <th>Body Weight</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plans as $key => $plan)
                        @php
                            $farmerName = trim((optional($plan->farmer)->first_name ?? '').' '.(optional($plan->farmer)->last_name ?? '')) ?: '-';
                            $animalName = trim((optional($plan->animal)->animal_name ?? '').(!empty(optional($plan->animal)->tag_number) ? ' ('.optional($plan->animal)->tag_number.')' : ''));
                            $panName = trim((optional($plan->pan)->name ?? ''));
                            $target = $panName !== '' ? 'PAN - '.$panName : ($animalName !== '' ? 'Animal - '.$animalName : '-');
                            $dateText = optional($plan->reference_date)->format('d-m-Y') ?: '-';
                            $dateValue = optional($plan->reference_date)->format('Y-m-d') ?: '';
                            $feedType = optional($plan->feedType)->name ?: '-';
                            $status = $plan->is_active ? 'Active' : 'Inactive';
                            $searchText = strtolower(trim(implode(' ', [
                                $farmerName,
                                $target,
                                $plan->diet_plan_name,
                                $feedType,
                                $plan->plan_quantity,
                                $plan->planned_dry_matter,
                                $plan->target_dmi,
                                $plan->milk_production,
                                $plan->body_weight,
                                $dateText,
                                $status,
                            ])));
                        @endphp
                        <tr class="diet-plan-row"
                            data-all="{{ $searchText }}"
                            data-farmer="{{ strtolower($farmerName) }}"
                            data-target="{{ strtolower($target) }}"
                            data-diet-plan-name="{{ strtolower((string) ($plan->diet_plan_name ?? '-')) }}"
                            data-feed-type="{{ strtolower($feedType) }}"
                            data-package-quantity="{{ strtolower(number_format((float) ($plan->plan_quantity ?? 0), 2)) }}"
                            data-planned-dry-matter="{{ strtolower(number_format((float) ($plan->planned_dry_matter ?? 0), 2)) }}"
                            data-target-dmi="{{ strtolower(number_format((float) ($plan->target_dmi ?? 0), 2)) }}"
                            data-milk-production="{{ strtolower(number_format((float) ($plan->milk_production ?? 0), 2)) }}"
                            data-body-weight="{{ strtolower(number_format((float) ($plan->body_weight ?? 0), 2)) }}"
                            data-date-text="{{ strtolower($dateText) }}"
                            data-status="{{ strtolower($status) }}"
                            data-date="{{ $dateValue }}">
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $farmerName }}</td>
                            <td>{{ $target }}</td>
                            <td>{{ $plan->diet_plan_name ?: '-' }}</td>
                            <td>{{ $feedType }}</td>
                            <td>{{ number_format((float) ($plan->plan_quantity ?? 0), 2) }} {{ $plan->unit ?: 'Kg' }}</td>
                            <td>{{ number_format((float) ($plan->planned_dry_matter ?? 0), 2) }} Kg</td>
                            <td>{{ number_format((float) ($plan->target_dmi ?? 0), 2) }} Kg</td>
                            <td>{{ number_format((float) ($plan->milk_production ?? 0), 2) }} L</td>
                            <td>{{ number_format((float) ($plan->body_weight ?? 0), 2) }} Kg</td>
                            <td>{{ $dateText }}</td>
                            <td>
                                <span class="badge {{ $plan->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                    {{ $status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted">No diet plans found</td>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('dietPlanSearch');
    const searchField = document.getElementById('dietPlanSearchField');
    const start = document.getElementById('startDate');
    const end = document.getElementById('endDate');
    const rows = Array.from(document.querySelectorAll('.diet-plan-row'));

    function datasetValue(row, field) {
        const key = field.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
        return row.dataset[key] || '';
    }

    function applyFilters() {
        const term = (search?.value || '').trim().toLowerCase();
        const selectedField = searchField?.value || 'all';
        const startDate = start?.value || '';
        const endDate = end?.value || '';

        rows.forEach((row) => {
            const text = selectedField === 'all'
                ? (row.dataset.all || '')
                : datasetValue(row, selectedField);
            const date = row.dataset.date || '';
            const matchesSearch = !term || text.includes(term);
            const matchesStart = !startDate || (date && date >= startDate);
            const matchesEnd = !endDate || (date && date <= endDate);
            row.style.display = matchesSearch && matchesStart && matchesEnd ? '' : 'none';
        });
    }

    [search, searchField, start, end].forEach((element) => {
        if (!element) return;
        element.addEventListener('input', applyFilters);
        element.addEventListener('change', applyFilters);
    });
});
</script>
@endpush
