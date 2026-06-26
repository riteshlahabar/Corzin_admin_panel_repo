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
            <h4 class="page-title mb-0">Edit Pregnancy Record</h4>
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
            <form method="POST" action="{{ route('farmer.pregnancy.update', $pregnancy) }}" id="pregnancyEditForm">
                @csrf
                @method('PUT')

                <div class="pregnancy-info-grid mb-3">
                    <div class="pregnancy-info-box">
                        <div class="pregnancy-info-label">Pregnancy No</div>
                        <div class="pregnancy-info-value">{{ $pregnancy->pregnancy_no ?? '-' }}</div>
                    </div>
                    <div class="pregnancy-info-box">
                        <div class="pregnancy-info-label">Service No</div>
                        <div class="pregnancy-info-value">{{ $pregnancy->service_no ?? '-' }}</div>
                    </div>
                    <div class="pregnancy-info-box">
                        <div class="pregnancy-info-label">Lactation Number</div>
                        <div class="pregnancy-info-value"><span id="lactationText">{{ old('lactation_number', (int) ($pregnancy->animal->lactation_number ?? 0)) }}</span></div>
                    </div>
                </div>

                <div class="pregnancy-section-card">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="pregnancy-label">Select Cow <span class="req">*</span></label>
                            <input type="hidden" name="animal_id" value="{{ $pregnancy->animal_id }}">
                            <input type="text" class="form-control" value="{{ $pregnancy->animal->animal_name ?? '-' }}{{ !empty($pregnancy->animal->tag_number) ? ' (Tag: '.$pregnancy->animal->tag_number.')' : '' }} - {{ trim(($pregnancy->animal->farmer->first_name ?? '').' '.($pregnancy->animal->farmer->last_name ?? '')) }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="pregnancy-label">Lactation Number</label>
                            <input type="number" min="0" name="lactation_number" id="lactationNumberInput" class="form-control" value="{{ old('lactation_number', (int) ($pregnancy->animal->lactation_number ?? 0)) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="pregnancy-label">Heat Date</label>
                            <input type="date" name="heat_date" class="form-control" value="{{ old('heat_date', optional($pregnancy->heat_date)->toDateString()) }}" max="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-md-6">
                            <label class="pregnancy-label">AI Date <span class="req">*</span></label>
                            <input type="date" name="ai_date" id="aiDateInput" class="form-control" value="{{ old('ai_date', optional($pregnancy->ai_date)->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="pregnancy-label">Service Type <span class="req">*</span></label>
                            <select name="service_type" class="form-select" required>
                                <option value="ai" {{ old('service_type', $pregnancy->service_type) === 'ai' ? 'selected' : '' }}>AI</option>
                                <option value="natural" {{ old('service_type', $pregnancy->service_type) === 'natural' ? 'selected' : '' }}>Natural</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="pregnancy-label">Pregnancy Check Due Date <span class="req">*</span></label>
                            <input type="date" name="pregnancy_check_due_date" id="checkDueInput" class="form-control" value="{{ old('pregnancy_check_due_date', optional($pregnancy->pregnancy_check_due_date)->toDateString()) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="pregnancy-label">Bull Name</label>
                            <input type="text" name="bull_name" class="form-control" value="{{ old('bull_name', $pregnancy->bull_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="pregnancy-label">Semen No</label>
                            <input type="text" name="semen_no" class="form-control" value="{{ old('semen_no', $pregnancy->semen_no) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="pregnancy-label">Doctor Name</label>
                            <input type="text" name="doctor_name" class="form-control" value="{{ old('doctor_name', $pregnancy->doctor_name) }}">
                        </div>
                        <div class="col-12">
                            <label class="pregnancy-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $pregnancy->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-success px-4">
                        <i class="fa-solid fa-save me-1"></i> Update Record
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
    const lactationInput = document.getElementById('lactationNumberInput');
    const aiDateInput = document.getElementById('aiDateInput');
    const checkDueInput = document.getElementById('checkDueInput');
    const lactationText = document.getElementById('lactationText');

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

    lactationInput?.addEventListener('input', function () {
        lactationText.textContent = lactationInput.value || '0';
    });
    aiDateInput?.addEventListener('change', autoDates);
});
</script>
@endpush
