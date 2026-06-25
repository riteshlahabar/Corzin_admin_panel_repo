@extends('layouts.app')

@push('styles')
<style>
    .diet-app-card {
        border: 0;
        border-radius: 22px;
        background: #fff;
        box-shadow: 0 12px 28px rgba(20, 54, 20, 0.08);
    }
    .diet-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }
    .diet-summary-box {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 12px;
    }
    .diet-summary-weight {
        background: linear-gradient(135deg, #eefaf0 0%, #ddf4e3 100%);
        border-color: #b7e1c2;
    }
    .diet-summary-milk {
        background: linear-gradient(135deg, #eef6ff 0%, #deebff 100%);
        border-color: #bbd4ff;
    }
    .diet-summary-dmi {
        background: linear-gradient(135deg, #fff7e8 0%, #ffedc9 100%);
        border-color: #f5d394;
    }
    .diet-summary-gap {
        background: linear-gradient(135deg, #fff1f1 0%, #ffdede 100%);
        border-color: #f2b3b3;
    }
    .diet-summary-dry {
        background: linear-gradient(135deg, #f5efff 0%, #e8ddff 100%);
        border-color: #cdb8ff;
    }
    .diet-summary-feed {
        background: linear-gradient(135deg, #eaf9f6 0%, #d7f1ec 100%);
        border-color: #a6ddd2;
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
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 14px;
    }
    .diet-block {
        background: #fff;
        border: 1px solid #e5e7eb;
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
        border: 1px solid #e5e7eb;
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
    <div class="row mb-4 mt-2">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">Edit Diet Plan</h4>
            <a href="{{ route('farmer.diet-plan') }}" class="btn btn-light border">
                <i class="fa-solid fa-list me-1"></i> Diet Plan List
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card diet-app-card">
        <div class="card-body p-3 p-lg-4">
            <form method="POST" action="{{ route('farmer.diet-plan.update', $plan) }}" class="diet-plan-form" id="dietPlanEditForm">
                @csrf
                @method('PUT')

                <div class="diet-summary-grid">
                    <div class="diet-summary-box diet-summary-weight">
                        <div class="diet-summary-label">Body Weight</div>
                        <div class="diet-summary-value"><span data-summary="body-weight">{{ number_format((float) old('body_weight', $plan->body_weight ?? 0), 2, '.', '') }}</span> <small>Kg</small></div>
                    </div>
                    <div class="diet-summary-box diet-summary-milk">
                        <div class="diet-summary-label">Milk Production</div>
                        <div class="diet-summary-value"><span data-summary="milk-production">{{ number_format((float) old('milk_production', $plan->milk_production ?? 0), 2, '.', '') }}</span> <small>L</small></div>
                    </div>
                    <div class="diet-summary-box diet-summary-dmi">
                        <div class="diet-summary-label">Required DMI</div>
                        <div class="diet-summary-value"><span data-summary="target-dmi">{{ number_format((float) old('target_dmi', $plan->target_dmi ?? 0), 2, '.', '') }}</span> <small>Kg</small></div>
                    </div>
                    <div class="diet-summary-box diet-summary-gap">
                        <div class="diet-summary-label">Gap</div>
                        <div class="diet-summary-value"><span data-summary="dmi-gap">{{ number_format((float) old('dmi_gap', $plan->dmi_gap ?? 0), 2, '.', '') }}</span> <small>Kg</small></div>
                    </div>
                    <div class="diet-summary-box diet-summary-dry">
                        <div class="diet-summary-label">Dry Matter</div>
                        <div class="diet-summary-value"><span data-summary="planned-dry-matter">{{ number_format((float) old('planned_dry_matter', $plan->planned_dry_matter ?? 0), 2, '.', '') }}</span> <small>Kg</small></div>
                    </div>
                    <div class="diet-summary-box diet-summary-feed">
                        <div class="diet-summary-label">Total Feeding</div>
                        <div class="diet-summary-value"><span data-summary="package-quantity">{{ number_format((float) old('plan_quantity', $plan->plan_quantity ?? 0), 2, '.', '') }}</span> <small>Kg</small></div>
                    </div>
                </div>

                <div class="diet-package-box mt-3">
                    <div class="fw-bold mb-3" style="font-size:14px;">Daily Feeding Package</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Farmer</label>
                            <select name="farmer_id" class="form-select diet-plan-farmer" required>
                                <option value="">Select farmer</option>
                                @foreach($farmers as $farmer)
                                    <option value="{{ $farmer->id }}" {{ (int) old('farmer_id', $plan->farmer_id) === (int) $farmer->id ? 'selected' : '' }}>
                                        {{ trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) }} - {{ $farmer->mobile }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Choose Animal</label>
                            <select name="animal_id" class="form-select diet-plan-animal">
                                <option value="">Select animal</option>
                                @foreach($animals as $animal)
                                    <option
                                        value="{{ $animal->id }}"
                                        data-farmer-id="{{ $animal->farmer_id }}"
                                        data-body-weight="{{ number_format((float) data_get($animalMetrics, $animal->id.'.body_weight', 0), 2, '.', '') }}"
                                        data-milk-production="{{ number_format((float) data_get($animalMetrics, $animal->id.'.milk_production', 0), 2, '.', '') }}"
                                        data-target-dmi="{{ number_format((float) data_get($animalMetrics, $animal->id.'.target_dmi', 0), 2, '.', '') }}"
                                        {{ (int) old('animal_id', ($plan->pan_id ? 0 : $plan->animal_id)) === (int) $animal->id ? 'selected' : '' }}
                                    >
                                        {{ $animal->animal_name }}{{ $animal->tag_number ? ' - '.$animal->tag_number : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Select Pen</label>
                            <select name="pan_id" class="form-select diet-plan-pan">
                                <option value="">No pen</option>
                                @foreach($pans as $pan)
                                    <option
                                        value="{{ $pan->id }}"
                                        data-farmer-id="{{ $pan->farmer_id }}"
                                        data-body-weight="{{ number_format((float) data_get($panMetrics, $pan->id.'.body_weight', 0), 2, '.', '') }}"
                                        data-milk-production="{{ number_format((float) data_get($panMetrics, $pan->id.'.milk_production', 0), 2, '.', '') }}"
                                        data-target-dmi="{{ number_format((float) data_get($panMetrics, $pan->id.'.target_dmi', 0), 2, '.', '') }}"
                                        {{ (int) old('pan_id', $plan->pan_id) === (int) $pan->id ? 'selected' : '' }}
                                    >
                                        {{ $pan->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Diet Plan Name</label>
                            <input type="text" name="diet_plan_name" class="form-control" placeholder="Enter diet plan name" value="{{ old('diet_plan_name', $plan->diet_plan_name) }}" required>
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <div class="small text-muted">
                                Select either one animal or one pen. Existing feed type and subtype values are loaded below for editing.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <div id="dietFeedBlocks"></div>
                    <button type="button" class="btn btn-link text-success fw-bold px-1 mt-2" id="addFeedBlockBtn">
                        <i class="fa-solid fa-plus-circle me-1"></i> Add More Feed
                    </button>
                </div>

                <input type="hidden" name="unit" id="dietPlanUnit" value="{{ old('unit', $plan->unit ?: 'Kg') }}">
                <input type="hidden" name="feed_type_id" id="dietPlanPrimaryFeedType" value="{{ old('feed_type_id', $plan->feed_type_id ?: '') }}">
                <input type="hidden" name="subtype_details_text" value="">
                <input type="hidden" name="reference_date" value="{{ old('reference_date', optional($plan->reference_date)->format('Y-m-d') ?: now()->toDateString()) }}">
                <input type="hidden" name="body_weight" class="diet-input-body-weight" value="{{ number_format((float) old('body_weight', $plan->body_weight ?? 0), 2, '.', '') }}">
                <input type="hidden" name="milk_production" class="diet-input-milk-production" value="{{ number_format((float) old('milk_production', $plan->milk_production ?? 0), 2, '.', '') }}">
                <input type="hidden" name="target_dmi" class="diet-input-target-dmi" value="{{ number_format((float) old('target_dmi', $plan->target_dmi ?? 0), 2, '.', '') }}">

                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" value="1" id="dietPlanActive" name="is_active" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="dietPlanActive">Active</label>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <a href="{{ route('farmer.diet-plan') }}" class="btn btn-light border">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to List
                    </a>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="fa-solid fa-save me-1"></i> Update Diet Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editForm = document.getElementById('dietPlanEditForm');
    const feedBlocksContainer = document.getElementById('dietFeedBlocks');
    const addFeedBlockBtn = document.getElementById('addFeedBlockBtn');
    const feedTypes = @json($feedTypesJson);
    const initialFeedTypeId = @json((string) old('feed_type_id', $plan->feed_type_id ?? ''));
    const initialSubtypeDetails = @json(old('subtype_details', $plan->subtype_details ?? []));

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

    function setMetrics(bodyWeight, milkProduction, targetDmi) {
        if (!editForm) return;

        const bodyWeightValue = parseFloat(bodyWeight || '0') || 0;
        const milkProductionValue = parseFloat(milkProduction || '0') || 0;
        const targetDmiValue = parseFloat(targetDmi || '0') || 0;

        const bodyWeightInput = editForm.querySelector('.diet-input-body-weight');
        const milkProductionInput = editForm.querySelector('.diet-input-milk-production');
        const targetDmiInput = editForm.querySelector('.diet-input-target-dmi');

        if (bodyWeightInput) bodyWeightInput.value = bodyWeightValue.toFixed(2);
        if (milkProductionInput) milkProductionInput.value = milkProductionValue.toFixed(2);
        if (targetDmiInput) targetDmiInput.value = targetDmiValue.toFixed(2);
    }

    function syncContextSelection(source) {
        if (!editForm) return;

        const animalSelect = editForm.querySelector('.diet-plan-animal');
        const panSelect = editForm.querySelector('.diet-plan-pan');
        if (!animalSelect || !panSelect) return;

        if (source === 'animal' && animalSelect.value) {
            panSelect.value = '';
        }

        if (source === 'pan' && panSelect.value) {
            animalSelect.value = '';
        }

        const selectedAnimal = animalSelect.selectedOptions[0];
        const selectedPan = panSelect.selectedOptions[0];

        if (panSelect.value && selectedPan) {
            setMetrics(
                selectedPan.getAttribute('data-body-weight'),
                selectedPan.getAttribute('data-milk-production'),
                selectedPan.getAttribute('data-target-dmi'),
            );
            return;
        }

        if (animalSelect.value && selectedAnimal) {
            setMetrics(
                selectedAnimal.getAttribute('data-body-weight'),
                selectedAnimal.getAttribute('data-milk-production'),
                selectedAnimal.getAttribute('data-target-dmi'),
            );
            return;
        }
    }

    function updateSummary() {
        if (!editForm) return;

        const bodyWeight = parseFloat(editForm.querySelector('.diet-input-body-weight')?.value || '0') || 0;
        const milkProduction = parseFloat(editForm.querySelector('.diet-input-milk-production')?.value || '0') || 0;
        const targetDmi = parseFloat(editForm.querySelector('.diet-input-target-dmi')?.value || '0') || 0;

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
            'dmi-gap': (plannedDryMatter - targetDmi).toFixed(2),
            'planned-dry-matter': plannedDryMatter.toFixed(2),
            'package-quantity': packageQuantity.toFixed(2),
        };

        Object.entries(summary).forEach(([key, value]) => {
            const node = editForm.querySelector(`[data-summary="${key}"]`);
            if (node) {
                node.textContent = value;
            }
        });

        const unitInput = document.getElementById('dietPlanUnit');
        if (unitInput) {
            const selectedType = editForm.querySelector('.diet-feed-type-select');
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
        if (!editForm) return;

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

        editForm.querySelectorAll('input[name^="subtype_details["]').forEach((input) => input.remove());

        fields.forEach((item, index) => {
            ['name', 'quantity', 'dm_percent'].forEach((key) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `subtype_details[${index}][${key}]`;
                input.value = item[key] || '';
                editForm.appendChild(input);
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

    function normalizedSubtypeDetails(details) {
        return Array.isArray(details)
            ? details.map((item) => ({
                name: String(item?.name || '').trim().toLowerCase(),
                quantity: String(item?.quantity || ''),
                dm_percent: String(item?.dm_percent || ''),
            }))
            : [];
    }

    function renderSubtypeCards(block, typeId, initialDetails = []) {
        const container = block.querySelector('.diet-subtypes-wrap');
        const type = feedTypes.find((item) => String(item.id) === String(typeId));

        if (!container) return;
        if (!type) {
            container.innerHTML = '';
            updateSummary();
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

        const detailMap = normalizedSubtypeDetails(initialDetails);
        container.querySelectorAll('.diet-subtype-card').forEach((card) => {
            const name = String(card.getAttribute('data-name') || '').trim().toLowerCase();
            const match = detailMap.find((item) => item.name === name);
            if (!match) {
                return;
            }

            const checkbox = card.querySelector('.diet-subtype-check');
            const qty = card.querySelector('.diet-subtype-qty');
            const dm = card.querySelector('.diet-subtype-dm');
            if (checkbox) checkbox.checked = true;
            if (qty) qty.value = match.quantity;
            if (dm) dm.value = match.dm_percent;
        });

        container.querySelectorAll('input').forEach((input) => {
            input.addEventListener('input', function () {
                updateSummary();
                refreshSubtypeNames();
            });
            input.addEventListener('change', function () {
                updateSummary();
                refreshSubtypeNames();
            });
        });

        updateSummary();
        refreshSubtypeNames();
    }

    function attachBlockEvents(block) {
        const typeSelect = block.querySelector('.diet-feed-type-select');
        const removeBtn = block.querySelector('.diet-remove-block');
        if (typeSelect) {
            typeSelect.addEventListener('change', function () {
                renderSubtypeCards(block, typeSelect.value, []);
                feedBlocksContainer.querySelectorAll('.diet-feed-type-select').forEach((select) => {
                    const current = select.value;
                    select.innerHTML = feedTypeOptionsMarkup(current);
                    select.value = current;
                });
                updateSummary();
            });
        }
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                if (feedBlocksContainer.querySelectorAll('.diet-block').length <= 1) {
                    block.querySelector('.diet-feed-type-select').value = '';
                    renderSubtypeCards(block, '', []);
                    block.querySelector('.diet-feed-type-select').innerHTML = feedTypeOptionsMarkup('');
                    return;
                }
                block.remove();
                feedBlocksContainer.querySelectorAll('.diet-feed-type-select').forEach((select) => {
                    const current = select.value;
                    select.innerHTML = feedTypeOptionsMarkup(current);
                    select.value = current;
                });
                updateSummary();
                refreshSubtypeNames();
            });
        }
    }

    function addFeedBlock(selectedTypeId = '', initialDetails = []) {
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
        renderSubtypeCards(block, selectedTypeId, initialDetails);
    }

    if (!editForm) return;

    const farmerSelect = editForm.querySelector('.diet-plan-farmer');
    farmerSelect?.addEventListener('change', function () {
        syncTargetOptions(editForm);
        syncContextSelection('');
        updateSummary();
    });
    syncTargetOptions(editForm);
    syncContextSelection('');

    editForm.querySelector('.diet-plan-animal')?.addEventListener('change', function () {
        syncContextSelection('animal');
        updateSummary();
    });
    editForm.querySelector('.diet-plan-pan')?.addEventListener('change', function () {
        syncContextSelection('pan');
        updateSummary();
    });

    addFeedBlock(initialFeedTypeId, initialSubtypeDetails);
    addFeedBlockBtn?.addEventListener('click', function () {
        if (selectedFeedTypeIds().length >= feedTypes.length) {
            return;
        }
        addFeedBlock();
    });

    editForm.addEventListener('submit', function (event) {
        const animalValue = editForm.querySelector('.diet-plan-animal')?.value || '';
        const panValue = editForm.querySelector('.diet-plan-pan')?.value || '';
        if (!animalValue && !panValue) {
            window.alert('Please select one animal or one pen.');
            event.preventDefault();
            return;
        }
        refreshSubtypeNames();
    });
});
</script>
@endpush
