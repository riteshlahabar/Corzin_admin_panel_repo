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
                @perm('diet_plan.add')
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addDietPlanModal">
                    <i class="fa-solid fa-plus me-1"></i> Add Diet Plan
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
                            <th>Action</th>
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
                            $subtypeText = collect((array) ($plan->subtype_details ?? []))
                                ->map(function ($item) {
                                    $name = trim((string) data_get($item, 'name', ''));
                                    $quantity = number_format((float) data_get($item, 'quantity', 0), 2, '.', '');
                                    $dmPercent = number_format((float) data_get($item, 'dm_percent', 0), 2, '.', '');
                                    return $name !== '' ? "{$name}|{$quantity}|{$dmPercent}" : null;
                                })
                                ->filter()
                                ->implode("\n");
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
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    @perm('diet_plan.edit')
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editDietPlan{{ $plan->id }}">
                                        Edit
                                    </button>
                                    @endperm
                                    @perm('diet_plan.delete')
                                    <form method="POST" action="{{ route('farmer.diet-plan.destroy', $plan) }}" onsubmit="return confirm('Delete this diet plan?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                    @endperm
                                </div>
                            </td>
                        </tr>

                        @perm('diet_plan.edit')
                        <div class="modal fade" id="editDietPlan{{ $plan->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('farmer.diet-plan.update', $plan) }}" class="diet-plan-form">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title">Edit Diet Plan</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">Farmer</label>
                                                    <select name="farmer_id" class="form-select diet-plan-farmer" required>
                                                        <option value="">Select farmer</option>
                                                        @foreach($farmers as $farmer)
                                                            <option value="{{ $farmer->id }}" {{ (int) $plan->farmer_id === (int) $farmer->id ? 'selected' : '' }}>
                                                                {{ trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) }} - {{ $farmer->mobile }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Animal</label>
                                                    <select name="animal_id" class="form-select diet-plan-animal" required>
                                                        <option value="">Select animal</option>
                                                        @foreach($animals as $animal)
                                                            <option
                                                                value="{{ $animal->id }}"
                                                                data-farmer-id="{{ $animal->farmer_id }}"
                                                                {{ (int) $plan->animal_id === (int) $animal->id ? 'selected' : '' }}
                                                            >
                                                                {{ $animal->animal_name }}{{ $animal->tag_number ? ' - '.$animal->tag_number : '' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Pen</label>
                                                    <select name="pan_id" class="form-select diet-plan-pan">
                                                        <option value="">No pen</option>
                                                        @foreach($pans as $pan)
                                                            <option
                                                                value="{{ $pan->id }}"
                                                                data-farmer-id="{{ $pan->farmer_id }}"
                                                                {{ (int) ($plan->pan_id ?? 0) === (int) $pan->id ? 'selected' : '' }}
                                                            >
                                                                {{ $pan->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Diet Plan Name</label>
                                                    <input type="text" name="diet_plan_name" class="form-control" value="{{ $plan->diet_plan_name }}" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Feed Type</label>
                                                    <select name="feed_type_id" class="form-select" required>
                                                        <option value="">Select feed type</option>
                                                        @foreach($feedTypes as $feedTypeItem)
                                                            <option value="{{ $feedTypeItem->id }}" {{ (int) $plan->feed_type_id === (int) $feedTypeItem->id ? 'selected' : '' }}>
                                                                {{ $feedTypeItem->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Reference Date</label>
                                                    <input type="date" name="reference_date" class="form-control" value="{{ optional($plan->reference_date)->format('Y-m-d') }}" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Body Weight</label>
                                                    <input type="number" step="0.01" min="0" name="body_weight" class="form-control" value="{{ number_format((float) ($plan->body_weight ?? 0), 2, '.', '') }}" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Milk Production</label>
                                                    <input type="number" step="0.01" min="0" name="milk_production" class="form-control" value="{{ number_format((float) ($plan->milk_production ?? 0), 2, '.', '') }}" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Required DMI</label>
                                                    <input type="number" step="0.01" min="0" name="target_dmi" class="form-control" value="{{ number_format((float) ($plan->target_dmi ?? 0), 2, '.', '') }}" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Days Count</label>
                                                    <input type="number" min="1" max="365" name="days_count" class="form-control" value="{{ $plan->days_count }}">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Unit</label>
                                                    <input type="text" name="unit" class="form-control" value="{{ $plan->unit ?: 'Kg' }}" required>
                                                </div>
                                                <div class="col-md-9">
                                                    <label class="form-label">Subtype Details</label>
                                                    <textarea name="subtype_details_text" rows="5" class="form-control" required>{{ $subtypeText }}</textarea>
                                                    <small class="text-muted">One line per item: <code>Name|Quantity|DM%</code></small>
                                                </div>
                                                <div class="col-12">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" value="1" id="dietActive{{ $plan->id }}" name="is_active" {{ $plan->is_active ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="dietActive{{ $plan->id }}">Active</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success">Update Diet Plan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endperm
                        @empty
                        <tr>
                            <td colspan="13" class="text-center text-muted">No diet plans found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@perm('diet_plan.add')
<div class="modal fade" id="addDietPlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('farmer.diet-plan.store') }}" class="diet-plan-form">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Add Diet Plan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Farmer</label>
                            <select name="farmer_id" class="form-select diet-plan-farmer" required>
                                <option value="">Select farmer</option>
                                @foreach($farmers as $farmer)
                                    <option value="{{ $farmer->id }}">
                                        {{ trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) }} - {{ $farmer->mobile }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Animal</label>
                            <select name="animal_id" class="form-select diet-plan-animal" required>
                                <option value="">Select animal</option>
                                @foreach($animals as $animal)
                                    <option value="{{ $animal->id }}" data-farmer-id="{{ $animal->farmer_id }}">
                                        {{ $animal->animal_name }}{{ $animal->tag_number ? ' - '.$animal->tag_number : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pen</label>
                            <select name="pan_id" class="form-select diet-plan-pan">
                                <option value="">No pen</option>
                                @foreach($pans as $pan)
                                    <option value="{{ $pan->id }}" data-farmer-id="{{ $pan->farmer_id }}">
                                        {{ $pan->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Diet Plan Name</label>
                            <input type="text" name="diet_plan_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Feed Type</label>
                            <select name="feed_type_id" class="form-select" required>
                                <option value="">Select feed type</option>
                                @foreach($feedTypes as $feedTypeItem)
                                    <option value="{{ $feedTypeItem->id }}">{{ $feedTypeItem->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reference Date</label>
                            <input type="date" name="reference_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Body Weight</label>
                            <input type="number" step="0.01" min="0" name="body_weight" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Milk Production</label>
                            <input type="number" step="0.01" min="0" name="milk_production" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Required DMI</label>
                            <input type="number" step="0.01" min="0" name="target_dmi" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Days Count</label>
                            <input type="number" min="1" max="365" name="days_count" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit" class="form-control" value="Kg" required>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label">Subtype Details</label>
                            <textarea name="subtype_details_text" rows="5" class="form-control" required></textarea>
                            <small class="text-muted">One line per item: <code>Name|Quantity|DM%</code></small>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="dietActiveCreate" name="is_active" checked>
                                <label class="form-check-label" for="dietActiveCreate">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Diet Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endperm
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

    function syncTargetOptions(form) {
        if (!form) return;

        const farmerSelect = form.querySelector('.diet-plan-farmer');
        const animalSelect = form.querySelector('.diet-plan-animal');
        const panSelect = form.querySelector('.diet-plan-pan');
        if (!farmerSelect || !animalSelect || !panSelect) {
            return;
        }

        const selectedFarmerId = farmerSelect.value;

        Array.from(animalSelect.options).forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                return;
            }
            option.hidden = selectedFarmerId !== '' && option.dataset.farmerId !== selectedFarmerId;
        });

        if (animalSelect.selectedOptions.length && animalSelect.selectedOptions[0].hidden) {
            animalSelect.value = '';
        }

        Array.from(panSelect.options).forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                return;
            }
            option.hidden = selectedFarmerId !== '' && option.dataset.farmerId !== selectedFarmerId;
        });

        if (panSelect.selectedOptions.length && panSelect.selectedOptions[0].hidden) {
            panSelect.value = '';
        }
    }

    [search, searchField, start, end].forEach((element) => {
        if (!element) return;
        element.addEventListener('input', applyFilters);
        element.addEventListener('change', applyFilters);
    });

    document.querySelectorAll('.diet-plan-form').forEach((form) => {
        const farmerSelect = form.querySelector('.diet-plan-farmer');
        if (farmerSelect) {
            farmerSelect.addEventListener('change', function () {
                syncTargetOptions(form);
            });
        }
        syncTargetOptions(form);
    });
});
</script>
@endpush
