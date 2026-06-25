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
            <h4 class="page-title mb-0">Add Diet Plan</h4>
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

    @include('feeding.diet_plan_create_form')
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const createForm = document.getElementById('dietPlanCreateForm');
    const feedBlocksContainer = document.getElementById('dietFeedBlocks');
    const addFeedBlockBtn = document.getElementById('addFeedBlockBtn');
    const feedTypes = @json($feedTypesJson);

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
        if (!createForm) return;

        const bodyWeightValue = parseFloat(bodyWeight || '0') || 0;
        const milkProductionValue = parseFloat(milkProduction || '0') || 0;
        const targetDmiValue = parseFloat(targetDmi || '0') || 0;

        const bodyWeightInput = createForm.querySelector('.diet-input-body-weight');
        const milkProductionInput = createForm.querySelector('.diet-input-milk-production');
        const targetDmiInput = createForm.querySelector('.diet-input-target-dmi');

        if (bodyWeightInput) bodyWeightInput.value = bodyWeightValue.toFixed(2);
        if (milkProductionInput) milkProductionInput.value = milkProductionValue.toFixed(2);
        if (targetDmiInput) targetDmiInput.value = targetDmiValue.toFixed(2);
    }

    function syncContextSelection(source) {
        if (!createForm) return;

        const animalSelect = createForm.querySelector('.diet-plan-animal');
        const panSelect = createForm.querySelector('.diet-plan-pan');
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

        setMetrics(0, 0, 0);
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
            'dmi-gap': (plannedDryMatter - targetDmi).toFixed(2),
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
    }

    if (!createForm) return;

    const farmerSelect = createForm.querySelector('.diet-plan-farmer');
    farmerSelect?.addEventListener('change', function () {
        syncTargetOptions(createForm);
        syncContextSelection('');
        updateCreateSummary();
    });
    syncTargetOptions(createForm);
    syncContextSelection('');

    createForm.querySelector('.diet-plan-animal')?.addEventListener('change', function () {
        syncContextSelection('animal');
        updateCreateSummary();
    });
    createForm.querySelector('.diet-plan-pan')?.addEventListener('change', function () {
        syncContextSelection('pan');
        updateCreateSummary();
    });

    addFeedBlock();
    addFeedBlockBtn?.addEventListener('click', function () {
        if (selectedFeedTypeIds().length >= feedTypes.length) {
            return;
        }
        addFeedBlock();
    });

    createForm.addEventListener('submit', function (event) {
        const animalValue = createForm.querySelector('.diet-plan-animal')?.value || '';
        const panValue = createForm.querySelector('.diet-plan-pan')?.value || '';
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
