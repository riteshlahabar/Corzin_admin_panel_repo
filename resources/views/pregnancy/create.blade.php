@extends('layouts.app')

@push('styles')
<style>
    .pregnancy-app-card {
        border: 0;
        border-radius: 22px;
        background: #fff;
        box-shadow: 0 12px 28px rgba(24, 55, 29, 0.08);
    }
    .pregnancy-section-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 16px;
    }
    .pregnancy-info-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }
    .pregnancy-info-box {
        border-radius: 16px;
        padding: 12px;
        border: 1px solid #d9e9db;
        background: linear-gradient(135deg, #eefaf0 0%, #ddf4e3 100%);
    }
    .pregnancy-info-label {
        color: #5f6f60;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 4px;
    }
    .pregnancy-info-value {
        color: #182817;
        font-size: 18px;
        font-weight: 800;
        line-height: 1.1;
    }
    .pregnancy-label {
        font-size: 13px;
        font-weight: 700;
        color: #20301f;
        margin-bottom: 8px;
    }
    .pregnancy-label .req {
        color: #e11d48;
    }
    @media (max-width: 991.98px) {
        .pregnancy-info-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 575.98px) {
        .pregnancy-info-grid {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-4 mt-2">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">Add Pregnancy Record</h4>
            <a href="{{ route('farmer.pregnancy') }}" class="btn btn-light border">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Pregnancy List
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

    <div class="card pregnancy-app-card">
        <div class="card-body p-3 p-lg-4">
            <form method="POST" action="{{ route('farmer.pregnancy.store') }}" id="pregnancyCreateForm">
                @csrf

                <div class="pregnancy-section-card">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="pregnancy-label">Farmer <span class="req">*</span></label>
                            <select id="pregnancyFarmer" class="form-select" required>
                                <option value="">Select farmer</option>
                                @foreach($farmers as $farmer)
                                    <option value="{{ $farmer->id }}">
                                        {{ trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) }}{{ !empty($farmer->mobile) ? ' - '.$farmer->mobile : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="pregnancy-label">Select Animal <span class="req">*</span></label>
                            <select name="animal_id" id="pregnancyAnimal" class="form-select" required>
                                <option value="">Select animal</option>
                                @foreach($animals as $animal)
                                    <option
                                        value="{{ $animal->id }}"
                                        data-farmer-id="{{ $animal->farmer_id }}"
                                        data-pregnancy-no="{{ (int) data_get($animalDefaults, $animal->id.'.pregnancy_no', 1) }}"
                                        data-service-no="{{ (int) data_get($animalDefaults, $animal->id.'.service_no', 1) }}"
                                        data-lactation-number="{{ (int) data_get($animalDefaults, $animal->id.'.lactation_number', 0) }}"
                                        {{ (int) old('animal_id') === (int) $animal->id ? 'selected' : '' }}
                                    >
                                        {{ $animal->animal_name }}{{ $animal->tag_number ? ' (Tag: '.$animal->tag_number.')' : '' }} - {{ trim(($animal->farmer->first_name ?? '').' '.($animal->farmer->last_name ?? '')) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="pregnancy-label">Lactation Number</label>
                            <input type="number" min="0" name="lactation_number" id="lactationNumberInput" class="form-control" value="{{ old('lactation_number') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="pregnancy-label">Heat Date</label>
                            <input type="date" name="heat_date" class="form-control" value="{{ old('heat_date') }}" max="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-md-6">
                            <label class="pregnancy-label">AI Date <span class="req">*</span></label>
                            <input type="date" name="ai_date" id="aiDateInput" class="form-control" value="{{ old('ai_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="pregnancy-label">Service Type <span class="req">*</span></label>
                            <select name="service_type" class="form-select" required>
                                <option value="ai" {{ old('service_type', 'ai') === 'ai' ? 'selected' : '' }}>AI</option>
                                <option value="natural" {{ old('service_type') === 'natural' ? 'selected' : '' }}>Natural</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="pregnancy-label">Pregnancy Check Due Date <span class="req">*</span></label>
                            <input type="date" name="pregnancy_check_due_date" id="checkDueInput" class="form-control" value="{{ old('pregnancy_check_due_date') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="pregnancy-label">Bull Name</label>
                            <input type="text" name="bull_name" class="form-control" value="{{ old('bull_name') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="pregnancy-label">Semen No</label>
                            <input type="text" name="semen_no" class="form-control" value="{{ old('semen_no') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="pregnancy-label">Doctor Name</label>
                            <input type="text" name="doctor_name" class="form-control" value="{{ old('doctor_name') }}">
                        </div>
                        <div class="col-12">
                            <label class="pregnancy-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-success px-4">
                        <i class="fa-solid fa-save me-1"></i> Save Record
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
    const farmerSelect = document.getElementById('pregnancyFarmer');
    const animalSelect = document.getElementById('pregnancyAnimal');
    const lactationInput = document.getElementById('lactationNumberInput');
    const aiDateInput = document.getElementById('aiDateInput');
    const checkDueInput = document.getElementById('checkDueInput');
    const pregnancyNoText = document.getElementById('pregnancyNoText');
    const serviceNoText = document.getElementById('serviceNoText');
    const lactationText = document.getElementById('lactationText');
    const originalAnimalOptions = Array.from(animalSelect?.options || []).map((option) => ({
        value: option.value,
        text: option.text,
        farmerId: option.getAttribute('data-farmer-id') || '',
        pregnancyNo: option.getAttribute('data-pregnancy-no') || '1',
        serviceNo: option.getAttribute('data-service-no') || '1',
        lactationNumber: option.getAttribute('data-lactation-number') || '0',
        selected: option.selected,
    }));

    function rebuildAnimalOptions() {
        if (!farmerSelect || !animalSelect) {
            return;
        }

        const selectedFarmerId = farmerSelect.value || '';
        const previousValue = animalSelect.value || '';
        animalSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select animal';
        animalSelect.appendChild(placeholder);

        originalAnimalOptions.forEach((optionData) => {
            if (!optionData.value) {
                return;
            }
            if (selectedFarmerId && optionData.farmerId !== selectedFarmerId) {
                return;
            }

            const option = document.createElement('option');
            option.value = optionData.value;
            option.textContent = optionData.text;
            option.setAttribute('data-farmer-id', optionData.farmerId);
            option.setAttribute('data-pregnancy-no', optionData.pregnancyNo);
            option.setAttribute('data-service-no', optionData.serviceNo);
            option.setAttribute('data-lactation-number', optionData.lactationNumber);
            if (optionData.value === previousValue) {
                option.selected = true;
            }
            animalSelect.appendChild(option);
        });
    }

    function syncAnimalDefaults() {
        const option = animalSelect?.selectedOptions?.[0];
        if (!option || !animalSelect.value) {
            return;
        }

        const pregnancyNo = option.getAttribute('data-pregnancy-no') || '1';
        const serviceNo = option.getAttribute('data-service-no') || '1';
        const lactationNumber = option.getAttribute('data-lactation-number') || '0';

        if (pregnancyNoText) {
            pregnancyNoText.textContent = pregnancyNo;
        }
        if (serviceNoText) {
            serviceNoText.textContent = serviceNo;
        }
        if (lactationInput && !lactationInput.value) {
            lactationInput.value = lactationNumber;
        }
        if (lactationText) {
            lactationText.textContent = lactationInput?.value || lactationNumber;
        }
    }

    function autoDates() {
        if (!aiDateInput?.value) {
            return;
        }

        const aiDate = new Date(aiDateInput.value + 'T00:00:00');
        if (Number.isNaN(aiDate.getTime())) {
            return;
        }

        const dueDate = new Date(aiDate);
        dueDate.setDate(dueDate.getDate() + 30);
        checkDueInput.value = dueDate.toISOString().slice(0, 10);
    }

    farmerSelect?.addEventListener('change', function () {
        rebuildAnimalOptions();
        syncAnimalDefaults();
    });
    animalSelect?.addEventListener('change', syncAnimalDefaults);
    lactationInput?.addEventListener('input', function () {
    });
    aiDateInput?.addEventListener('change', autoDates);

    rebuildAnimalOptions();
    syncAnimalDefaults();
    if (!checkDueInput.value) {
        autoDates();
    }
});
</script>
@endpush
