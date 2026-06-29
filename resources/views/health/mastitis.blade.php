@extends('layouts.app')

@push('styles')
<link href="{{ asset('assets/libs/mobius1-selectr/selectr.min.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row mb-4 mt-2">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">{{ $title }}</h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <select id="healthSearchField" class="form-select" style="width:190px;">
                    <option value="all">All Columns</option>
                    <option value="farmer">Farmer</option>
                    <option value="animal">Animal</option>
                    <option value="tag">Tag</option>
                    <option value="test-result">Test Result</option>
                    <option value="treatment">Treatment</option>
                    <option value="recovery-status">Recovery Status</option>
                    <option value="date-text">Date</option>
                </select>
                <input type="text" id="healthSearch" class="form-control" placeholder="Search selected field..." style="width:220px;">
                <div class="input-group" style="width:260px;">
                    <input type="date" id="startDate" class="form-control">
                    <span class="input-group-text">to</span>
                    <input type="date" id="endDate" class="form-control">
                </div>
                <button type="button" class="btn btn-light border" onclick="exportTableToPdf('healthTableExport', '{{ $title }}')" title="Download PDF">
                    <i class="fa-solid fa-file-pdf text-danger"></i>
                </button>
                <button type="button" class="btn btn-light border" onclick="exportTableToExcel('healthTableExport', 'mastitis-records')" title="Download Excel">
                    <i class="fa-solid fa-file-excel text-success"></i>
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mastitisModal">
                    <i class="fa-solid fa-plus me-1"></i> Add Mastitis
                </button>
            </div>
        </div>
    </div>

    <div class="card mt-2">
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table mb-0" id="healthTableExport">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Farmer</th>
                            <th>Animal</th>
                            <th>Tag</th>
                            <th>Test Result</th>
                            <th>Treatment</th>
                            <th>Recovery Status</th>
                            <th>Positive Found Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $key => $row)
                            <tr
                                class="health-row"
                                data-all="{{ strtolower(trim(($row->farmer->first_name ?? '').' '.($row->farmer->last_name ?? '').' '.($row->animal->animal_name ?? '').' '.($row->animal->tag_number ?? '').' '.($row->test_result ?? '').' '.($row->treatment ?? '').' '.($row->recovery_status ?? '').' '.($row->quarter ?? '').' '.($row->clinical_type ?? '').' '.($row->cmt_score ?? '').' '.($row->scc_count ?? '').' '.(optional($row->date)->format('d-m-Y') ?? '').' '.(optional($row->follow_up_date)->format('d-m-Y') ?? '').' '.($row->notes ?? ''))) }}"
                                data-farmer="{{ strtolower(trim(($row->farmer->first_name ?? '').' '.($row->farmer->last_name ?? ''))) }}"
                                data-animal="{{ strtolower($row->animal->animal_name ?? '') }}"
                                data-tag="{{ strtolower($row->animal->tag_number ?? '') }}"
                                data-test-result="{{ strtolower($row->test_result ?? '') }}"
                                data-treatment="{{ strtolower($row->treatment ?? '') }}"
                                data-recovery-status="{{ strtolower($row->recovery_status ?? '') }}"
                                data-date-text="{{ strtolower(optional($row->date)->format('d-m-Y') ?? '') }}"
                                data-date="{{ optional($row->date)->format('Y-m-d') }}"
                            >
                                <td>{{ $key + 1 }}</td>
                                <td>{{ trim(($row->farmer->first_name ?? '').' '.($row->farmer->last_name ?? '')) ?: '-' }}</td>
                                <td>{{ $row->animal->animal_name ?? '-' }}</td>
                                <td>{{ $row->animal->tag_number ?? '-' }}</td>
                                <td>{{ $row->test_result }}</td>
                                <td>{{ $row->treatment ?: '-' }}</td>
                                <td>{{ $row->recovery_status }}</td>
                                <td>{{ optional($row->date)->format('d-m-Y') }}</td>
                                <td>
                                    @php
                                        $status = strtolower(str_replace(' ', '_', (string) ($row->recovery_status ?? '')));
                                        $isRecovered = in_array($status, ['recovered', 'recoverd'], true);
                                        $caseId = $row->case_id ?: $row->id;
                                    @endphp
                                    @if($isRecovered)
                                        <span class="text-muted">-</span>
                                    @else
                                        <div class="d-flex gap-1 flex-wrap">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary js-open-treatment-modal"
                                                data-record-id="{{ $caseId }}"
                                                data-animal-name="{{ $row->animal->animal_name ?? '-' }}"
                                                data-tag-number="{{ $row->animal->tag_number ?? '-' }}"
                                            >
                                                Add Treatment
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-success js-open-recovered-modal"
                                                data-record-id="{{ $caseId }}"
                                                data-animal-name="{{ $row->animal->animal_name ?? '-' }}"
                                                data-tag-number="{{ $row->animal->tag_number ?? '-' }}"
                                            >
                                                Recovered
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No mastitis records found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="mastitisModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('health.mastitis.store') }}">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Add Mastitis Record</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Farmer</label>
                            <select name="farmer_id" id="mastitisFarmerSelect" class="form-select" required>
                                <option value="">Select farmer</option>
                                @foreach($farmers as $farmer)
                                    <option value="{{ $farmer->id }}">
                                        {{ trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) }} - {{ $farmer->mobile }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Animal</label>
                            <select name="animal_id" id="mastitisAnimalSelect" class="form-select" required>
                                <option value="">Select animal</option>
                                @foreach($animals as $animal)
                                    <option
                                        value="{{ $animal->id }}"
                                        data-farmer-id="{{ $animal->farmer_id }}"
                                    >
                                        {{ $animal->animal_name }} - {{ $animal->tag_number ?: '-' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Test Result</label>
                            <select name="test_result" class="form-select" required>
                                <option value="positive">Positive</option>
                                <option value="negative">Negative</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="mastitisTreatmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('health.mastitis.treatment') }}">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Add Treatment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="mastitis_record_id" id="mastitisTreatmentRecordId">
                    <div class="alert alert-light border py-2 px-3 mb-3">
                        <span class="fw-semibold" id="mastitisTreatmentAnimalText">-</span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Treatment</label>
                        <input type="text" name="treatment" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}">
                    </div>
                    <div>
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="3" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Treatment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="mastitisRecoveredModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('health.mastitis.recover') }}">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Mark Recovered</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="mastitis_record_id" id="mastitisRecoveredRecordId">
                    <p class="mb-3">Mark <span class="fw-semibold" id="mastitisRecoveredAnimalText">-</span> as recovered?</p>
                    <div>
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Recovered</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/health/index.js') }}"></script>
<script src="{{ asset('assets/libs/mobius1-selectr/selectr.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const farmerSelect = document.getElementById('mastitisFarmerSelect');
    const animalSelect = document.getElementById('mastitisAnimalSelect');
    const originalAnimalOptions = Array.from(animalSelect ? animalSelect.options : []).map(function (option) {
        return {
            value: option.value,
            text: option.text,
            farmerId: option.getAttribute('data-farmer-id') || '',
        };
    });
    let farmerSelectr = null;
    let animalSelectr = null;

    if (!farmerSelect || !animalSelect) {
        return;
    }

    const initSearchableSelect = (element) => {
        return new Selectr(element, {
            searchable: true,
            clearable: false,
            placeholder: element.options[0] ? element.options[0].text : 'Select an option...',
        });
    };

    const rebuildAnimalSelect = (selectedFarmerId) => {
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
            animalSelect.appendChild(option);
        });

        animalSelect.value = '';

        if (animalSelectr) {
            animalSelectr.destroy();
        }
        animalSelectr = initSearchableSelect(animalSelect);
    };

    farmerSelect.addEventListener('change', function () {
        rebuildAnimalSelect(farmerSelect.value);
    });

    farmerSelectr = initSearchableSelect(farmerSelect);
    rebuildAnimalSelect(farmerSelect.value);

    const treatmentModalElement = document.getElementById('mastitisTreatmentModal');
    const recoveredModalElement = document.getElementById('mastitisRecoveredModal');
    const treatmentModal = treatmentModalElement ? new bootstrap.Modal(treatmentModalElement) : null;
    const recoveredModal = recoveredModalElement ? new bootstrap.Modal(recoveredModalElement) : null;
    const treatmentRecordId = document.getElementById('mastitisTreatmentRecordId');
    const recoveredRecordId = document.getElementById('mastitisRecoveredRecordId');
    const treatmentAnimalText = document.getElementById('mastitisTreatmentAnimalText');
    const recoveredAnimalText = document.getElementById('mastitisRecoveredAnimalText');

    document.querySelectorAll('.js-open-treatment-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            const recordId = button.getAttribute('data-record-id') || '';
            const animalName = button.getAttribute('data-animal-name') || '-';
            const tagNumber = button.getAttribute('data-tag-number') || '-';
            if (treatmentRecordId) {
                treatmentRecordId.value = recordId;
            }
            if (treatmentAnimalText) {
                treatmentAnimalText.textContent = `${animalName} (${tagNumber})`;
            }
            treatmentModal?.show();
        });
    });

    document.querySelectorAll('.js-open-recovered-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            const recordId = button.getAttribute('data-record-id') || '';
            const animalName = button.getAttribute('data-animal-name') || '-';
            const tagNumber = button.getAttribute('data-tag-number') || '-';
            if (recoveredRecordId) {
                recoveredRecordId.value = recordId;
            }
            if (recoveredAnimalText) {
                recoveredAnimalText.textContent = `${animalName} (${tagNumber})`;
            }
            recoveredModal?.show();
        });
    });
});
</script>
@endpush
