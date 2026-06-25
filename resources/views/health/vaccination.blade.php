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
                    <option value="pan-name">Pen Name</option>
                    <option value="vaccine">Vaccine</option>
                    <option value="doses">Doses</option>
                    <option value="date-text">Date</option>
                    <option value="notes">Notes</option>
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
                <button type="button" class="btn btn-light border" onclick="exportTableToExcel('healthTableExport', 'vaccination-records')" title="Download Excel">
                    <i class="fa-solid fa-file-excel text-success"></i>
                </button>
                @perm('health_vaccination.add')
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#vaccinationModal">
                    <i class="fa-solid fa-plus me-1"></i> Add Vaccination
                </button>
                @endperm
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
                            <th>Pen Name</th>
                            <th>Vaccine</th>
                            <th>Doses</th>
                            <th>Date</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $key => $row)
                            <tr
                                class="health-row"
                                data-all="{{ strtolower(trim(($row->farmer->first_name ?? '').' '.($row->farmer->last_name ?? '').' '.($row->animal->animal_name ?? '').' '.($row->animal->tag_number ?? '').' '.($row->pan_name ?? '').' '.($row->vaccine->name ?? '').' '.($row->doses ?? '').' '.(optional($row->vaccination_date)->format('d-m-Y') ?? '').' '.($row->notes ?? ''))) }}"
                                data-farmer="{{ strtolower(trim(($row->farmer->first_name ?? '').' '.($row->farmer->last_name ?? ''))) }}"
                                data-animal="{{ strtolower($row->animal->animal_name ?? '') }}"
                                data-tag="{{ strtolower($row->animal->tag_number ?? '') }}"
                                data-pan-name="{{ strtolower($row->pan_name ?? '') }}"
                                data-vaccine="{{ strtolower($row->vaccine->name ?? '') }}"
                                data-doses="{{ strtolower($row->doses ?? '') }}"
                                data-date-text="{{ strtolower(optional($row->vaccination_date)->format('d-m-Y') ?? '') }}"
                                data-notes="{{ strtolower($row->notes ?? '') }}"
                                data-date="{{ optional($row->vaccination_date)->format('Y-m-d') }}"
                            >
                                <td>{{ $key + 1 }}</td>
                                <td>{{ trim(($row->farmer->first_name ?? '').' '.($row->farmer->last_name ?? '')) ?: '-' }}</td>
                                <td>{{ $row->animal->animal_name ?? '-' }}</td>
                                <td>{{ $row->animal->tag_number ?? '-' }}</td>
                                <td>{{ $row->pan_name ?: '-' }}</td>
                                <td>{{ $row->vaccine->name ?? '-' }}</td>
                                <td>{{ $row->doses }}</td>
                                <td>{{ optional($row->vaccination_date)->format('d-m-Y') }}</td>
                                <td>{{ $row->notes ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No vaccination records found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@perm('health_vaccination.add')
<div class="modal fade" id="vaccinationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('health.vaccination.store') }}">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Add Vaccination Record</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Farmer</label>
                            <select name="farmer_id" id="vaccinationFarmerSelect" class="form-select" required>
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
                            <select name="animal_id" id="vaccinationAnimalSelect" class="form-select" required>
                                <option value="">Select animal</option>
                                @foreach($animals as $animal)
                                    <option
                                        value="{{ $animal->id }}"
                                        data-farmer-id="{{ $animal->farmer_id }}"
                                        data-pan-name="{{ $animal->pan->name ?? '' }}"
                                    >
                                        {{ $animal->animal_name }} - {{ $animal->tag_number ?: '-' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pen Name</label>
                            <input type="text" id="vaccinationPanName" class="form-control" placeholder="Pen name" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vaccination Name</label>
                            <select name="vaccine_id" class="form-select" required>
                                <option value="">Select vaccine</option>
                                @foreach($vaccines as $vaccine)
                                    <option value="{{ $vaccine->id }}">{{ $vaccine->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Doses</label>
                            <input type="text" name="doses" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" name="vaccination_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" rows="3" class="form-control"></textarea>
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
@endperm
@endsection

@push('scripts')
<script src="{{ asset('js/health/index.js') }}"></script>
<script src="{{ asset('assets/libs/mobius1-selectr/selectr.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const farmerSelect = document.getElementById('vaccinationFarmerSelect');
    const animalSelect = document.getElementById('vaccinationAnimalSelect');
    const panInput = document.getElementById('vaccinationPanName');
    const originalAnimalOptions = Array.from(animalSelect ? animalSelect.options : []).map(function (option) {
        return {
            value: option.value,
            text: option.text,
            farmerId: option.getAttribute('data-farmer-id') || '',
            panName: option.getAttribute('data-pan-name') || '',
            selected: option.selected,
        };
    });
    let farmerSelectr = null;
    let animalSelectr = null;

    if (!farmerSelect || !animalSelect || !panInput) {
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
            option.setAttribute('data-pan-name', optionData.panName);
            animalSelect.appendChild(option);
        });

        animalSelect.value = '';
        panInput.value = '';

        if (animalSelectr) {
            animalSelectr.destroy();
        }
        animalSelectr = initSearchableSelect(animalSelect);
    };

    const syncPanName = () => {
        const option = animalSelect.options[animalSelect.selectedIndex];
        panInput.value = option ? (option.getAttribute('data-pan-name') || '') : '';
    };

    farmerSelect.addEventListener('change', function () {
        rebuildAnimalSelect(farmerSelect.value);
    });
    animalSelect.addEventListener('change', syncPanName);

    farmerSelectr = initSearchableSelect(farmerSelect);
    rebuildAnimalSelect(farmerSelect.value);
});
</script>
@endpush
