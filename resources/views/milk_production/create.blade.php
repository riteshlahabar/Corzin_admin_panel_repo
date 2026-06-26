@extends('layouts.app')

@push('styles')
<style>
    .milk-app-card {
        border: 0;
        border-radius: 22px;
        background: #fff;
        box-shadow: 0 12px 28px rgba(24, 55, 29, 0.08);
    }
    .milk-section-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 16px;
    }
    .milk-label {
        font-size: 13px;
        font-weight: 700;
        color: #20301f;
        margin-bottom: 8px;
    }
    .milk-label .req {
        color: #e11d48;
    }
    .milk-help {
        font-size: 12px;
        color: #6b7280;
    }
    .milk-shift-chip {
        border-radius: 999px;
        padding: 6px 12px;
        border: 1px solid #cfe3d0;
        background: #f7fbf7;
        font-size: 12px;
        font-weight: 700;
        color: #2d5b34;
    }
    .milk-cow-card {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 12px;
        background: #fcfdfc;
    }
    .milk-cow-title {
        font-size: 14px;
        font-weight: 800;
        color: #1f2937;
    }
    .milk-cow-meta {
        font-size: 12px;
        color: #6b7280;
    }
    .milk-status-box {
        border-radius: 16px;
        padding: 12px 14px;
        border: 1px solid #d8e9da;
        background: #f7fbf7;
    }
    .milk-status-box.bad {
        border-color: #f3c1c1;
        background: #fff4f4;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-4 mt-2">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">Add Milk</h4>
            <a href="{{ route('farmer.milk') }}" class="btn btn-light border">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Milk Production
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

    <div class="card milk-app-card">
        <div class="card-body p-3 p-lg-4">
            <form method="POST" action="{{ route('farmer.milk.store') }}" id="milkCreateForm">
                @csrf

                <div class="milk-section-card mb-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="milk-label">Farmer <span class="req">*</span></label>
                            <select name="farmer_id" id="milkFarmer" class="form-select" required>
                                <option value="">Select farmer</option>
                                @foreach($farmers as $farmer)
                                    <option value="{{ $farmer->id }}" {{ (int) old('farmer_id') === (int) $farmer->id ? 'selected' : '' }}>
                                        {{ trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) }} - {{ $farmer->mobile }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="milk-label">Choose Animal</label>
                            <select name="animal_id" id="milkAnimal" class="form-select">
                                <option value="">Select animal</option>
                                @foreach($animals as $animal)
                                    <option
                                        value="{{ $animal->id }}"
                                        data-farmer-id="{{ $animal->farmer_id }}"
                                        {{ (int) old('animal_id') === (int) $animal->id ? 'selected' : '' }}
                                    >
                                        {{ $animal->animal_name }}{{ $animal->tag_number ? ' - '.$animal->tag_number : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="milk-label">Select PAN</label>
                            <select name="pan_id" id="milkPan" class="form-select">
                                <option value="">Select PAN</option>
                                @foreach($pans as $pan)
                                    <option
                                        value="{{ $pan->id }}"
                                        data-farmer-id="{{ $pan->farmer_id }}"
                                        {{ (int) old('pan_id') === (int) $pan->id ? 'selected' : '' }}
                                    >
                                        {{ $pan->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="milk-label">Select Dairy</label>
                            <select name="dairy_id" id="milkDairy" class="form-select">
                                <option value="">Select dairy</option>
                                @foreach($dairies as $dairy)
                                    <option
                                        value="{{ $dairy->id }}"
                                        data-farmer-id="{{ $dairy->farmer_id }}"
                                        {{ (int) old('dairy_id') === (int) $dairy->id ? 'selected' : '' }}
                                    >
                                        {{ $dairy->dairy_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="milk-label">Milk Date <span class="req">*</span></label>
                            <input type="date" name="date" id="milkDate" class="form-control" value="{{ old('date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="milk-label">Shift <span class="req">*</span></label>
                            <select name="shift" id="milkShift" class="form-select" required>
                                <option value="">Select shift</option>
                                @foreach(['Morning', 'Afternoon', 'Evening'] as $shift)
                                    <option value="{{ $shift }}" {{ old('shift', 'Morning') === $shift ? 'selected' : '' }}>{{ $shift }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="milk-section-card mb-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="milk-label">Quantity Liters <span class="req">*</span></label>
                            <input type="number" step="0.01" min="0.1" name="quantity_liters" id="quantityLiters" class="form-control" value="{{ old('quantity_liters') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="milk-label">FAT <span class="req">*</span></label>
                            <input type="number" step="0.01" min="0" name="fat" class="form-control" value="{{ old('fat') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="milk-label">SNF <span class="req">*</span></label>
                            <input type="number" step="0.01" min="0" name="snf" class="form-control" value="{{ old('snf') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="milk-label">Rate Per Liter <span class="req">*</span></label>
                            <input type="number" step="0.01" min="0" name="rate" class="form-control" value="{{ old('rate') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="milk-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Optional notes">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <div id="panInfoWrap" class="milk-section-card mb-3 d-none">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <div class="milk-label mb-1">Cow Wise Milk Distribution</div>
                            <div class="milk-help">Enter milk quantity for every cow in selected PAN.</div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap" id="panShiftBadges"></div>
                    </div>

                    <div id="panCowRows" class="row g-3"></div>

                    <div class="milk-status-box mt-3" id="panStatusBox">
                        <div class="row g-2">
                            <div class="col-md-4"><strong>Entered Quantity:</strong> <span id="enteredQtyText">0.00</span> L</div>
                            <div class="col-md-4"><strong>Cow Total:</strong> <span id="cowTotalText">0.00</span> L</div>
                            <div class="col-md-4"><strong>Difference:</strong> <span id="cowDiffText">0.00</span> L</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-success px-4">
                        <i class="fa-solid fa-save me-1"></i> Save Milk Entry
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
    const form = document.getElementById('milkCreateForm');
    const farmerSelect = document.getElementById('milkFarmer');
    const animalSelect = document.getElementById('milkAnimal');
    const panSelect = document.getElementById('milkPan');
    const dairySelect = document.getElementById('milkDairy');
    const shiftSelect = document.getElementById('milkShift');
    const quantityInput = document.getElementById('quantityLiters');
    const panInfoWrap = document.getElementById('panInfoWrap');
    const panCowRows = document.getElementById('panCowRows');
    const panShiftBadges = document.getElementById('panShiftBadges');
    const enteredQtyText = document.getElementById('enteredQtyText');
    const cowTotalText = document.getElementById('cowTotalText');
    const cowDiffText = document.getElementById('cowDiffText');
    const panStatusBox = document.getElementById('panStatusBox');
    const pansData = @json($pansData);
    const oldCowDetails = @json(old('cow_milk_details', []));

    function filterOptions(select, farmerId) {
        Array.from(select.options).forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                return;
            }
            option.hidden = farmerId !== '' && option.dataset.farmerId !== farmerId;
        });

        if (select.selectedOptions.length && select.selectedOptions[0].hidden) {
            select.value = '';
        }
    }

    function syncFarmerOptions() {
        const farmerId = farmerSelect.value || '';
        filterOptions(animalSelect, farmerId);
        filterOptions(panSelect, farmerId);
        filterOptions(dairySelect, farmerId);
    }

    function oldCowValue(animalId) {
        const match = Array.isArray(oldCowDetails)
            ? oldCowDetails.find((item) => String(item?.animal_id || '') === String(animalId))
            : null;
        return match ? (match.final_milk_qty ?? '') : '';
    }

    function rebuildPanDetails() {
        const panId = panSelect.value || '';
        const pan = pansData[panId];

        if (!panId || !pan) {
            panInfoWrap.classList.add('d-none');
            panCowRows.innerHTML = '';
            panShiftBadges.innerHTML = '';
            updateCowTotals();
            return;
        }

        panInfoWrap.classList.remove('d-none');
        panShiftBadges.innerHTML = (pan.milk_shifts || []).map((shift) => `<span class="milk-shift-chip">${shift}</span>`).join('');
        panCowRows.innerHTML = (pan.cows || []).map((cow, index) => `
            <div class="col-md-6">
                <div class="milk-cow-card">
                    <div class="milk-cow-title">${cow.name}${cow.tag_number ? ` (${cow.tag_number})` : ''}</div>
                    <div class="milk-cow-meta">Default milk per session: ${Number(cow.default_milk_per_session || 0).toFixed(2)} L</div>
                    <input type="hidden" name="cow_milk_details[${index}][animal_id]" value="${cow.id}">
                    <div class="mt-2">
                        <label class="milk-label mb-1">Final Milk Qty <span class="req">*</span></label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-control pan-cow-qty"
                            name="cow_milk_details[${index}][final_milk_qty]"
                            value="${oldCowValue(cow.id) || Number(cow.default_milk_per_session || 0).toFixed(2)}"
                        >
                    </div>
                </div>
            </div>
        `).join('');

        panCowRows.querySelectorAll('.pan-cow-qty').forEach((input) => {
            input.addEventListener('input', updateCowTotals);
        });

        updateCowTotals();
    }

    function updateCowTotals() {
        const entered = parseFloat(quantityInput.value || '0') || 0;
        const total = Array.from(document.querySelectorAll('.pan-cow-qty'))
            .reduce((sum, input) => sum + (parseFloat(input.value || '0') || 0), 0);
        const diff = total - entered;

        enteredQtyText.textContent = entered.toFixed(2);
        cowTotalText.textContent = total.toFixed(2);
        cowDiffText.textContent = diff.toFixed(2);

        if (panSelect.value && Math.abs(diff) > 0.01) {
            panStatusBox.classList.add('bad');
        } else {
            panStatusBox.classList.remove('bad');
        }
    }

    farmerSelect?.addEventListener('change', function () {
        syncFarmerOptions();
        rebuildPanDetails();
    });

    animalSelect?.addEventListener('change', function () {
        if (animalSelect.value) {
            panSelect.value = '';
        }
        rebuildPanDetails();
    });

    panSelect?.addEventListener('change', function () {
        if (panSelect.value) {
            animalSelect.value = '';
        }
        rebuildPanDetails();
    });

    quantityInput?.addEventListener('input', updateCowTotals);
    shiftSelect?.addEventListener('change', updateCowTotals);

    form?.addEventListener('submit', function (event) {
        const animalId = animalSelect.value || '';
        const panId = panSelect.value || '';
        if (!animalId && !panId) {
            event.preventDefault();
            window.alert('Please select one animal or one PAN.');
            return;
        }
        if (animalId && panId) {
            event.preventDefault();
            window.alert('Please select either animal or PAN, not both.');
            return;
        }
        if (panId) {
            const entered = parseFloat(quantityInput.value || '0') || 0;
            const total = Array.from(document.querySelectorAll('.pan-cow-qty'))
                .reduce((sum, input) => sum + (parseFloat(input.value || '0') || 0), 0);
            if (Math.abs(total - entered) > 0.01) {
                event.preventDefault();
                window.alert('Cow-wise total must match quantity liters.');
            }
        }
    });

    syncFarmerOptions();
    rebuildPanDetails();
});
</script>
@endpush
