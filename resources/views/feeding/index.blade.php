@extends('layouts.app')

@push('styles')
<link href="{{ asset('assets/libs/mobius1-selectr/selectr.min.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row mb-4 mt-4 pt-2">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">Feeding Management</h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <select id="feedingSearchField" class="form-select" style="width:190px;">
                    <option value="all">All Columns</option>
                    <option value="farmer">Farmer</option>
                    <option value="animal">Animal</option>
                    <option value="feed-type">Feed Type</option>
                    <option value="quantity">Feeding Quantity</option>
                    <option value="time">Time</option>
                    <option value="date-text">Date</option>
                    <option value="notes">Notes</option>
                </select>
                <input type="text" id="feedingSearch" class="form-control" placeholder="Search selected field..." style="width:220px;">
                <div class="input-group" style="width:260px;">
                    <input type="date" id="startDate" class="form-control">
                    <span class="input-group-text">to</span>
                    <input type="date" id="endDate" class="form-control">
                </div>
                <button type="button" class="btn btn-light border" onclick="exportTableToPdf('feedingTableExport', 'Feeding Management')" title="Download PDF">
                    <i class="fa-solid fa-file-pdf text-danger"></i>
                </button>
                <button type="button" class="btn btn-light border" onclick="exportTableToExcel('feedingTableExport', 'feeding-management')" title="Download Excel">
                    <i class="fa-solid fa-file-excel text-success"></i>
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFeedingModal">
                    <i class="fa-solid fa-plus me-1"></i> Add Feeding
                </button>
            </div>
        </div>
    </div>

    <div class="card mt-2">
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table mb-0" id="feedingTableExport">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Farmer</th>
                            <th>Animal</th>
                            <th>Diet Plan</th>
                            <th>Feed Type</th>
                            <th>Feeding Quantity</th>
                            <th>Time</th>
                            <th>Date</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $key => $record)
                        <tr class="feeding-row"
                            data-all="{{ strtolower(trim(($record->farmer->first_name ?? '').' '.($record->farmer->last_name ?? '').' '.($record->animal->animal_name ?? '').' '.($record->animal->tag_number ?? '').' '.($record->dietPlan->diet_plan_name ?? '').' '.($record->feedType->name ?? '').' '.($record->quantity ?? '').' '.($record->feeding_time ?? '').' '.(optional($record->date)->format('d-m-Y') ?? '').' '.($record->notes ?? ''))) }}"
                            data-farmer="{{ strtolower(trim(($record->farmer->first_name ?? '').' '.($record->farmer->last_name ?? ''))) }}"
                            data-animal="{{ strtolower(trim(($record->animal->animal_name ?? '').' '.(!empty($record->animal->tag_number) ? 'tag '.$record->animal->tag_number : ''))) }}"
                            data-feed-type="{{ strtolower($record->feedType->name ?? '') }}"
                            data-quantity="{{ strtolower(number_format($record->quantity, 2)) }}"
                            data-time="{{ strtolower($record->feeding_time ?? '') }}"
                            data-date-text="{{ strtolower(optional($record->date)->format('d-m-Y') ?? '') }}"
                            data-notes="{{ strtolower($record->notes ?? '') }}"
                            data-date="{{ optional($record->date)->format('Y-m-d') }}">
                            <td>{{ $key + 1 }}</td>
                            <td>{{ trim(($record->farmer->first_name ?? '').' '.($record->farmer->last_name ?? '')) ?: '-' }}</td>
                            <td>{{ $record->animal->animal_name ?? '-' }}{{ !empty($record->animal->tag_number) ? ' - Tag '.$record->animal->tag_number : '' }}</td>
                            <td>{{ $record->dietPlan->diet_plan_name ?? '-' }}</td>
                            <td>{{ $record->feedType->name ?? '-' }}</td>
                            <td>{{ number_format($record->quantity, 2) }}</td>
                            <td>{{ $record->feeding_time }}</td>
                            <td>{{ optional($record->date)->format('d-m-Y') }}</td>
                            <td>{{ $record->notes ?: '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">No feeding records found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addFeedingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('farmer.feeding.store') }}">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title text-white">Add Feeding Entry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Farmer</label>
                            <select name="farmer_id" id="feedingFarmerSelect" class="form-select" required>
                                <option value="">Select farmer</option>
                                @foreach($farmers as $farmer)
                                    <option value="{{ $farmer->id }}">{{ trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) }} - {{ $farmer->mobile }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Animal</label>
                            <select name="animal_id" id="feedingAnimalSelect" class="form-select">
                                <option value="">Select animal</option>
                                @foreach($animals as $animal)
                                    <option value="{{ $animal->id }}" data-farmer-id="{{ $animal->farmer_id }}">
                                        {{ $animal->animal_name }} - {{ $animal->tag_number ?: '-' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pen</label>
                            <select name="pan_id" id="feedingPanSelect" class="form-select">
                                <option value="">Select pen</option>
                                @foreach($pans as $pan)
                                    <option value="{{ $pan->id }}" data-farmer-id="{{ $pan->farmer_id }}">
                                        {{ $pan->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Diet Plan</label>
                            <select name="diet_plan_id" id="feedingDietPlanSelect" class="form-select" required>
                                <option value="">Select diet plan</option>
                                @foreach($dietPlans as $plan)
                                    @php
                                        $ownerLabel = !empty($plan->pan_id)
                                            ? 'Pen - '.(optional($plan->pan)->name ?? '-')
                                            : 'Animal - '.trim((optional($plan->animal)->animal_name ?? '').(!empty(optional($plan->animal)->tag_number) ? ' - '.optional($plan->animal)->tag_number : ''));
                                        $planQuantityLabel = number_format((float) ($plan->plan_quantity ?? 0), 2).' '.($plan->unit ?: 'Kg');
                                    @endphp
                                    <option
                                        value="{{ $plan->id }}"
                                        data-farmer-id="{{ $plan->farmer_id }}"
                                        data-animal-id="{{ $plan->animal_id }}"
                                        data-pan-id="{{ $plan->pan_id }}"
                                    >
                                        {{ $plan->diet_plan_name ?: 'Diet Plan #'.$plan->id }} | {{ $planQuantityLabel }} | {{ $ownerLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Feeding Time</label>
                            <select name="feeding_time" class="form-select" required>
                                <option value="Morning">Morning</option>
                                <option value="Afternoon">Afternoon</option>
                                <option value="Evening">Evening</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Feeding Quantity</label>
                            <input type="number" step="0.01" min="0.01" name="quantity" id="feedingQuantityInput" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rate/Unit</label>
                            <input type="number" step="0.01" min="0" name="rate_per_unit" id="feedingRateInput" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Feeding Cost</label>
                            <input type="number" step="0.01" min="0" name="feeding_cost" id="feedingCostInput" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Feeding</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/feeding/index.js') }}"></script>
<script src="{{ asset('assets/libs/mobius1-selectr/selectr.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const farmerSelect = document.getElementById('feedingFarmerSelect');
    const animalSelect = document.getElementById('feedingAnimalSelect');
    const panSelect = document.getElementById('feedingPanSelect');
    const dietPlanSelect = document.getElementById('feedingDietPlanSelect');
    const quantityInput = document.getElementById('feedingQuantityInput');
    const rateInput = document.getElementById('feedingRateInput');
    const costInput = document.getElementById('feedingCostInput');

    if (!farmerSelect || !animalSelect || !panSelect || !dietPlanSelect || !quantityInput || !rateInput || !costInput) {
        return;
    }

    const animalOptions = Array.from(animalSelect.options).map((option) => ({
        value: option.value,
        text: option.text,
        farmerId: option.getAttribute('data-farmer-id') || '',
    }));
    const panOptions = Array.from(panSelect.options).map((option) => ({
        value: option.value,
        text: option.text,
        farmerId: option.getAttribute('data-farmer-id') || '',
    }));
    const dietPlanOptions = Array.from(dietPlanSelect.options).map((option) => ({
        value: option.value,
        text: option.text,
        farmerId: option.getAttribute('data-farmer-id') || '',
        animalId: option.getAttribute('data-animal-id') || '',
        panId: option.getAttribute('data-pan-id') || '',
    }));

    let farmerSelectr = null;
    let animalSelectr = null;
    let panSelectr = null;
    let dietPlanSelectr = null;

    const initSearchableSelect = (element, placeholderText) => new Selectr(element, {
        searchable: true,
        clearable: false,
        placeholder: placeholderText,
    });

    const rebuildSelect = (select, options, placeholderText, filterFn) => {
        select.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = placeholderText;
        select.appendChild(placeholder);

        options.forEach((optionData) => {
            if (!optionData.value) {
                return;
            }
            if (filterFn && !filterFn(optionData)) {
                return;
            }

            const option = document.createElement('option');
            option.value = optionData.value;
            option.textContent = optionData.text;
            if (optionData.farmerId) option.setAttribute('data-farmer-id', optionData.farmerId);
            if (optionData.animalId) option.setAttribute('data-animal-id', optionData.animalId);
            if (optionData.panId) option.setAttribute('data-pan-id', optionData.panId);
            select.appendChild(option);
        });

        select.value = '';
    };

    const refreshAnimalSelect = () => {
        const farmerId = farmerSelect.value || '';
        rebuildSelect(
            animalSelect,
            animalOptions,
            'Select animal',
            (optionData) => !farmerId || optionData.farmerId === farmerId,
        );
        if (animalSelectr) animalSelectr.destroy();
        animalSelectr = initSearchableSelect(animalSelect, 'Select animal');
    };

    const refreshPanSelect = () => {
        const farmerId = farmerSelect.value || '';
        rebuildSelect(
            panSelect,
            panOptions,
            'Select pen',
            (optionData) => !farmerId || optionData.farmerId === farmerId,
        );
        if (panSelectr) panSelectr.destroy();
        panSelectr = initSearchableSelect(panSelect, 'Select pen');
    };

    const refreshDietPlanSelect = () => {
        const farmerId = farmerSelect.value || '';
        const animalId = animalSelect.value || '';
        const panId = panSelect.value || '';

        rebuildSelect(
            dietPlanSelect,
            dietPlanOptions,
            'Select diet plan',
            (optionData) => {
                if (farmerId && optionData.farmerId !== farmerId) {
                    return false;
                }
                if (animalId) {
                    return optionData.animalId === animalId && !optionData.panId;
                }
                if (panId) {
                    return optionData.panId === panId;
                }
                return false;
            },
        );

        if (dietPlanSelectr) dietPlanSelectr.destroy();
        dietPlanSelectr = initSearchableSelect(dietPlanSelect, 'Select diet plan');
    };

    const updateFeedingCost = () => {
        const quantity = parseFloat(quantityInput.value || '0') || 0;
        const rate = parseFloat(rateInput.value || '0') || 0;
        costInput.value = (quantity * rate).toFixed(2);
    };

    farmerSelect.addEventListener('change', function () {
        refreshAnimalSelect();
        refreshPanSelect();
        refreshDietPlanSelect();
    });

    animalSelect.addEventListener('change', function () {
        if (animalSelect.value) {
            refreshPanSelect();
        }
        refreshDietPlanSelect();
    });

    panSelect.addEventListener('change', function () {
        if (panSelect.value) {
            refreshAnimalSelect();
        }
        refreshDietPlanSelect();
    });

    quantityInput.addEventListener('input', updateFeedingCost);
    rateInput.addEventListener('input', updateFeedingCost);

    farmerSelectr = initSearchableSelect(farmerSelect, 'Select farmer');
    refreshAnimalSelect();
    refreshPanSelect();
    refreshDietPlanSelect();
    updateFeedingCost();
});
</script>
@endpush
