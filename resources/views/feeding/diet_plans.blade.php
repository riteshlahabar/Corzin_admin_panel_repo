@extends('layouts.app')

@push('styles')
<style>
    .diet-app-card {
        border: 0;
        border-radius: 22px;
        background: #fff;
        box-shadow: 0 12px 28px rgba(20, 54, 20, 0.08);
    }
    .diet-app-hero {
        background: linear-gradient(135deg, #5aa75d 0%, #3f8d4c 100%);
        border-radius: 18px;
        padding: 14px;
        color: #fff;
    }
    .diet-app-hero-icon {
        width: 42px;
        height: 42px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.16);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .diet-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }
    .diet-summary-box {
        background: #f7fbf7;
        border: 1px solid #dfedde;
        border-radius: 16px;
        padding: 12px;
    }
    .diet-summary-label {
        color: #6d7a6c;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .diet-summary-value {
        color: #152515;
        font-size: 18px;
        font-weight: 800;
        line-height: 1.1;
    }
    .diet-package-box {
        background: #f8fcf8;
        border: 1px solid #e0efe1;
        border-radius: 16px;
        padding: 14px;
    }
    .diet-block {
        background: #fbfdfb;
        border: 1px solid #e1eee2;
        border-radius: 18px;
        padding: 14px;
    }
    .diet-block-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 10px;
    }
    .diet-block-title {
        font-size: 14px;
        font-weight: 800;
        color: #1e2b1d;
        margin: 0;
    }
    .diet-subtype-card {
        background: #fff;
        border: 1px solid #e5efe5;
        border-radius: 14px;
        padding: 12px;
    }
    .diet-subtype-name {
        font-weight: 700;
        color: #20301f;
    }
    .diet-subtype-meta {
        color: #768374;
        font-size: 12px;
    }
    .diet-hidden {
        display: none !important;
    }
    @media (max-width: 991.98px) {
        .diet-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 575.98px) {
        .diet-summary-grid {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row mb-4 mt-4 pt-2">
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
                <a href="{{ route('farmer.diet-plan.create') }}" class="btn btn-success">
                    <i class="fa-solid fa-plus me-1"></i> Add Diet Plan
                </a>
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
                            $target = $panName !== '' ? 'Pen - '.$panName : ($animalName !== '' ? 'Animal - '.$animalName : '-');
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
                                    <a href="{{ route('farmer.diet-plan.edit', $plan) }}" class="btn btn-sm btn-outline-primary">
                                        Edit
                                    </a>
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

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('dietPlanSearch');
    const searchField = document.getElementById('dietPlanSearchField');
    const start = document.getElementById('startDate');
    const end = document.getElementById('endDate');
    const rows = Array.from(document.querySelectorAll('.diet-plan-row'));
    const createForm = document.getElementById('dietPlanCreateForm');
    const feedBlocksContainer = document.getElementById('dietFeedBlocks');
    const addFeedBlockBtn = document.getElementById('addFeedBlockBtn');
    const feedTypes = @json($feedTypesJson);
    let blockIndex = 0;

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

    function updateCreateSummary() {
        if (!createForm) return;

        const bodyWeight = parseFloat(createForm.querySelector('.diet-input-body-weight')?.value || '0') || 0;
        const milkProduction = parseFloat(createForm.querySelector('.diet-input-milk-production')?.value || '0') || 0;
        const targetDmi = parseFloat(createForm.querySelector('.diet-input-target-dmi')?.value || '0') || 0;

        let plannedDryMatter = 0;
        let packageQuantity = 0;

        feedBlocksContainer?.querySelectorAll('.diet-subtype-card').forEach((card) => {
            const checkbox = card.querySelector('.diet-subtype-check');
            if (!checkbox || !checkbox.checked) {
                return;
            }

            const qty = parseFloat(card.querySelector('.diet-subtype-qty')?.value || '0') || 0;
            const dm = parseFloat(card.querySelector('.diet-subtype-dm')?.value || '0') || 0;
            packageQuantity += qty;
            plannedDryMatter += (qty * dm) / 100;
        });

        const summary = {
            'body-weight': bodyWeight.toFixed(2),
            'milk-production': milkProduction.toFixed(2),
            'target-dmi': targetDmi.toFixed(2),
            'planned-dry-matter': plannedDryMatter.toFixed(2),
            'package-quantity': packageQuantity.toFixed(2),
        };

        Object.entries(summary).forEach(([key, value]) => {
            const node = createForm.querySelector(`[data-summary="${key}"]`);
            if (node) {
                node.textContent = value;
            }
        });

        const unitInput = document.getElementById('dietPlanUnit');
        if (unitInput) {
            const selectedType = createForm.querySelector('.diet-feed-type-select');
            const type = feedTypes.find((item) => String(item.id) === String(selectedType?.value || ''));
            unitInput.value = type?.default_unit || 'Kg';
        }

        const primaryFeedTypeInput = document.getElementById('dietPlanPrimaryFeedType');
        if (primaryFeedTypeInput) {
            const firstSelected = Array.from(feedBlocksContainer?.querySelectorAll('.diet-feed-type-select') || [])
                .map((select) => select.value)
                .find((value) => value);
            primaryFeedTypeInput.value = firstSelected || '';
        }
    }

    function refreshSubtypeNames() {
        if (!createForm) return;

        const fields = [];
        feedBlocksContainer?.querySelectorAll('.diet-subtype-card').forEach((card) => {
            const checkbox = card.querySelector('.diet-subtype-check');
            if (!checkbox || !checkbox.checked) {
                return;
            }
            const name = card.getAttribute('data-name') || '';
            const qty = card.querySelector('.diet-subtype-qty')?.value || '';
            const dm = card.querySelector('.diet-subtype-dm')?.value || '';
            fields.push({ name, quantity: qty, dm_percent: dm });
        });

        createForm.querySelectorAll('input[name^="subtype_details["]').forEach((input) => input.remove());

        fields.forEach((item, index) => {
            ['name', 'quantity', 'dm_percent'].forEach((key) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `subtype_details[${index}][${key}]`;
                input.value = item[key] || '';
                createForm.appendChild(input);
            });
        });
    }

    function selectedFeedTypeIds() {
        return Array.from(feedBlocksContainer?.querySelectorAll('.diet-feed-type-select') || [])
            .map((select) => select.value)
            .filter((value) => value);
    }

    function feedTypeOptionsMarkup(currentValue = '') {
        const taken = selectedFeedTypeIds();
        return [
            '<option value="">Select feed type</option>',
            ...feedTypes
                .filter((type) => currentValue === String(type.id) || !taken.includes(String(type.id)))
                .map((type) => `<option value="${type.id}" ${currentValue === String(type.id) ? 'selected' : ''}>${type.name}</option>`),
        ].join('');
    }

    function renderSubtypeCards(block, typeId) {
        const container = block.querySelector('.diet-subtypes-wrap');
        const type = feedTypes.find((item) => String(item.id) === String(typeId));

        if (!container) return;
        if (!type) {
            container.innerHTML = '';
            updateCreateSummary();
            refreshSubtypeNames();
            return;
        }

        container.innerHTML = type.subtypes.map((subtype) => `
            <div class="col-md-6">
                <div class="diet-subtype-card" data-name="${subtype.name}">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <div class="diet-subtype-name">${subtype.name}</div>
                            <div class="diet-subtype-meta">Enter quantity and DM %</div>
                        </div>
                        <div class="form-check m-0">
                            <input class="form-check-input diet-subtype-check" type="checkbox">
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-6">
                            <label class="form-label small mb-1">Quantity</label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm diet-subtype-qty">
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1">DM %</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm diet-subtype-dm">
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        container.querySelectorAll('input').forEach((input) => {
            input.addEventListener('input', function () {
                updateCreateSummary();
                refreshSubtypeNames();
            });
            input.addEventListener('change', function () {
                updateCreateSummary();
                refreshSubtypeNames();
            });
        });

        updateCreateSummary();
        refreshSubtypeNames();
    }

    function attachBlockEvents(block) {
        const typeSelect = block.querySelector('.diet-feed-type-select');
        const removeBtn = block.querySelector('.diet-remove-block');
        if (typeSelect) {
            typeSelect.addEventListener('change', function () {
                renderSubtypeCards(block, typeSelect.value);
                feedBlocksContainer.querySelectorAll('.diet-feed-type-select').forEach((select) => {
                    const current = select.value;
                    select.innerHTML = feedTypeOptionsMarkup(current);
                    select.value = current;
                });
                updateCreateSummary();
            });
        }
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                if (feedBlocksContainer.querySelectorAll('.diet-block').length <= 1) {
                    block.querySelector('.diet-feed-type-select').value = '';
                    renderSubtypeCards(block, '');
                    block.querySelector('.diet-feed-type-select').innerHTML = feedTypeOptionsMarkup('');
                    return;
                }
                block.remove();
                feedBlocksContainer.querySelectorAll('.diet-feed-type-select').forEach((select) => {
                    const current = select.value;
                    select.innerHTML = feedTypeOptionsMarkup(current);
                    select.value = current;
                });
                updateCreateSummary();
                refreshSubtypeNames();
            });
        }
    }

    function addFeedBlock(selectedTypeId = '') {
        if (!feedBlocksContainer) return;

        const block = document.createElement('div');
        block.className = 'diet-block mb-3';
        block.innerHTML = `
            <div class="diet-block-head">
                <h6 class="diet-block-title mb-0">Feed Type Block</h6>
                <button type="button" class="btn btn-sm btn-outline-danger diet-remove-block">Remove</button>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Feed Type</label>
                    <select class="form-select diet-feed-type-select">
                        ${feedTypeOptionsMarkup(String(selectedTypeId || ''))}
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Subtypes</label>
                    <div class="row g-2 diet-subtypes-wrap"></div>
                </div>
            </div>
        `;
        feedBlocksContainer.appendChild(block);
        attachBlockEvents(block);
        const typeSelect = block.querySelector('.diet-feed-type-select');
        if (typeSelect && selectedTypeId) {
            typeSelect.value = String(selectedTypeId);
        }
        renderSubtypeCards(block, selectedTypeId);
        blockIndex++;
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

    if (createForm) {
        createForm.querySelector('.diet-input-body-weight')?.addEventListener('input', updateCreateSummary);
        createForm.querySelector('.diet-input-milk-production')?.addEventListener('input', updateCreateSummary);
        createForm.querySelector('.diet-input-target-dmi')?.addEventListener('input', updateCreateSummary);

        createForm.querySelector('.diet-plan-animal')?.addEventListener('change', function (event) {
            const option = event.target.selectedOptions[0];
            const weightInput = createForm.querySelector('.diet-input-body-weight');
            if (weightInput && option) {
                if (!weightInput.value) {
                    weightInput.value = option.getAttribute('data-weight') || '';
                }
            }
            updateCreateSummary();
        });

        addFeedBlock();
        addFeedBlockBtn?.addEventListener('click', function () {
            if (selectedFeedTypeIds().length >= feedTypes.length) {
                return;
            }
            addFeedBlock();
        });

        createForm.addEventListener('submit', function () {
            refreshSubtypeNames();
        });
    }
});
</script>
@endpush
