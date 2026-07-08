@extends('layouts.app')

@push('styles')
<style>
    .animal-type-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }
    .animal-type-card .card-body h5,
    .animal-type-card .card-body h2 {
        color: #fff;
    }
    .animal-type-card .card-body small {
        color: rgba(255, 255, 255, 0.72);
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('import_errors'))
        <div class="alert alert-warning">
            <div class="fw-semibold mb-1">Import completed with some row errors:</div>
            <ul class="mb-0 ps-3">
                @foreach(session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $cardStyles = [
            'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
            'linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%)',
            'linear-gradient(135deg, #64748b 0%, #475569 100%)',
            'linear-gradient(135deg, #22c55e 0%, #15803d 100%)',
            'linear-gradient(135deg, #6366f1 0%, #4338ca 100%)',
        ];
    @endphp
    <div class="row g-3 mb-4 mt-2">
        @foreach($animalTypes as $index => $type)
            <div class="col-md-6 col-lg-3">
                <div class="card animal-type-card" style="background: {{ $cardStyles[$index % count($cardStyles)] }};">
                    <div class="card-body text-center">
                        <h5 class="fw-bold mb-1" style="font-size:18px;">{{ $type->name }}</h5>
                        <h2 class="fw-bold mb-0">{{ (int) ($typeCounts[$type->id] ?? 0) }}</h2>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row mb-4">
        <div class="col-12 d-flex flex-column gap-2">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <h4 class="page-title mb-0">Animal List</h4>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="btn-group" role="group">
                        @foreach($animalTypes as $type)
                            <input type="checkbox" class="btn-check animal-filter" id="type{{ $type->id }}" value="{{ $type->id }}" checked>
                            <label class="btn btn-outline-primary active" for="type{{ $type->id }}">{{ $type->name }}</label>
                        @endforeach
                    </div>
                    <select id="animalSearchField" class="form-select" style="width:190px;">
                        <option value="all">All Columns</option>
                        <option value="unique-id">Unique ID</option>
                        <option value="farmer">Farmer</option>
                        <option value="animal-name">Animal Name</option>
                        <option value="tag-number">Tag Number</option>
                        <option value="lactation-number">Lactation No.</option>
                        <option value="ai-date">AI Date</option>
                        <option value="breed-name">Breed Name</option>
                        <option value="type-name">Type</option>
                        <option value="pan-name">Pen</option>
                        <option value="gender">Gender</option>
                        <option value="birth-date">Birth Date</option>
                        <option value="age">Age</option>
                        <option value="weight">Weight</option>
                        <option value="status">Status</option>
                    </select>
                    <input type="text" id="animalSearch" class="form-control" placeholder="Search selected field..." style="width:220px;">
                    <div class="input-group" style="width:260px;">
                        <input type="date" id="startDate" class="form-control">
                        <span class="input-group-text">to</span>
                        <input type="date" id="endDate" class="form-control">
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                @perm('animal_list.import')
                <a href="{{ route('animal.import.template') }}" class="btn btn-light border" title="Download Animal Import Template">
                    <i class="fa-solid fa-download me-1 text-primary"></i> Template
                </a>
                @endperm
                @perm('animal_list.import')
                <div class="d-flex flex-column align-items-end gap-1">
                    <form method="POST" action="{{ route('animal.import') }}" enctype="multipart/form-data" class="d-flex align-items-center gap-2 flex-wrap">
                        @csrf
                        <input type="file" name="file" class="form-control form-control-sm" accept=".csv,.txt,.xls,.xlsx" required style="max-width:220px;">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fa-solid fa-upload me-1"></i> Upload List
                        </button>
                    </form>
                    <div class="small text-muted text-end">
                        Use the template and fill <strong>pregnancy_status</strong> to create a pregnancy record for that animal. Upload supports CSV and Excel <strong>(.xlsx)</strong>. For old <strong>.xls</strong>, save as <strong>.xlsx</strong> first.
                    </div>
                </div>
                @endperm
                <button type="button" class="btn btn-light border" onclick="exportTableToPdf('animalTableExport', 'Animal List')" title="Download PDF">
                    <i class="fa-solid fa-file-pdf text-danger"></i>
                </button>
                <button type="button" class="btn btn-light border" onclick="exportTableToExcel('animalTableExport', 'animal-list')" title="Download Excel">
                    <i class="fa-solid fa-file-excel text-success"></i>
                </button>
                @perm('animal_list.add')
                <a href="{{ route('animal.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus me-1"></i> Add Animal
                </a>
                @endperm
            </div>
        </div>
    </div>

    <div class="card mt-2">
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table mb-0" id="animalTableExport">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Unique ID</th>
                            <th>Farmer</th>
                            <th>Animal Name</th>
                            <th>Tag Number</th>
                            <th>Lactation No.</th>
                            <th>AI Date</th>
                            <th>Breed Name</th>
                            <th>Type</th>
                            <th>Pen</th>
                            <th>Gender</th>
                            <th>Birth Date</th>
                            <th>Age</th>
                            <th>Weight</th>
                            <th>Image</th>
                            <th>Status</th>
                            <th class="text-end text-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($animals as $key => $animal)
                        <tr class="animal-row"
                            data-type="{{ $animal->animal_type_id }}"
                            data-all="{{ strtolower(trim(($animal->farmer->first_name ?? '').' '.($animal->farmer->last_name ?? '').' '.($animal->farmer->mobile ?? '').' '.($animal->animal_name ?? '').' '.($animal->tag_number ?? '').' '.($animal->unique_id ?? '').' '.($animal->breed_name ?? '').' '.($animal->lactation_number ?? '').' '.($animal->animalType->name ?? '').' '.($animal->pan->name ?? '').' '.($animal->gender ?? '').' '.($animal->birth_date ? \Carbon\Carbon::parse($animal->birth_date)->format('d-m-Y') : '').' '.($animal->calculated_age ?? '').' '.($animal->weight ?? '').' '.($animal->is_active ? 'active' : 'inactive'))) }}"
                            data-unique-id="{{ strtolower($animal->unique_id ?? '') }}"
                            data-farmer="{{ strtolower(trim(($animal->farmer->first_name ?? '').' '.($animal->farmer->last_name ?? ''))) }}"
                            data-animal-name="{{ strtolower($animal->animal_name ?? '') }}"
                            data-tag-number="{{ strtolower($animal->tag_number ?? '') }}"
                            data-lactation-number="{{ strtolower((string) ($animal->lactation_number ?? '')) }}"
                            data-ai-date="{{ strtolower($animal->ai_date ? \Carbon\Carbon::parse($animal->ai_date)->format('d-m-Y') : '') }}"
                            data-breed-name="{{ strtolower($animal->breed_name ?? '') }}"
                            data-type-name="{{ strtolower($animal->animalType->name ?? '') }}"
                            data-pan-name="{{ strtolower($animal->pan->name ?? '') }}"
                            data-gender="{{ strtolower($animal->gender ?? '') }}"
                            data-birth-date="{{ strtolower($animal->birth_date ? \Carbon\Carbon::parse($animal->birth_date)->format('d-m-Y') : '') }}"
                            data-age="{{ strtolower((string) ($animal->calculated_age ?? '')) }}"
                            data-weight="{{ strtolower((string) ($animal->weight ?? '')) }}"
                            data-status="{{ $animal->is_active ? 'active' : 'inactive' }}"
                            data-date="{{ optional($animal->created_at)->format('Y-m-d') }}">
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $animal->unique_id }}</td>
                            <td>{{ trim(($animal->farmer->first_name ?? '').' '.($animal->farmer->last_name ?? '')) ?: '-' }}</td>
                            <td>{{ $animal->animal_name }}</td>
                            <td>{{ $animal->tag_number }}</td>
                            <td>{{ $animal->lactation_number ?? '-' }}</td>
                            <td>{{ $animal->ai_date ? \Carbon\Carbon::parse($animal->ai_date)->format('d-m-Y') : '-' }}</td>
                            <td>{{ $animal->breed_name ?: '-' }}</td>
                            <td>{{ $animal->animalType->name ?? '-' }}</td>
                            <td>{{ $animal->pan->name ?? '-' }}</td>
                            <td>{{ $animal->gender }}</td>
                            <td>{{ $animal->birth_date ? \Carbon\Carbon::parse($animal->birth_date)->format('d-m-Y') : '-' }}</td>
                            <td>{{ $animal->calculated_age ?? '-' }}</td>
                            <td>{{ $animal->weight ? $animal->weight.' kg' : '-' }}</td>
                            <td>
                                @if($animal->image_url)
                                    <button type="button" class="btn btn-sm btn-light border view-animal-image" data-image="{{ $animal->image_url }}" data-animal="{{ $animal->animal_name }}">
                                        <i class="las la-eye text-primary fs-18"></i>
                                    </button>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $animal->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} text-capitalize">
                                    {{ $animal->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="d-inline-flex align-items-center gap-1 flex-nowrap">
                                    @perm('animal_list.edit')
                                    <a href="{{ route('animal.edit', $animal) }}" class="btn btn-sm btn-light border" title="Edit Animal">
                                        <i class="las la-pen text-primary fs-18"></i>
                                    </a>
                                    @endperm
                                    @perm('animal_list.status')
                                    <form method="POST" action="{{ route('animal.toggle', $animal) }}" class="d-inline m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $animal->is_active ? 'btn-success' : 'btn-danger' }}" title="{{ $animal->is_active ? 'Set Inactive' : 'Set Active' }}">
                                            <i class="las {{ $animal->is_active ? 'la-check-circle' : 'la-times-circle' }} fs-18"></i>
                                        </button>
                                    </form>
                                    @endperm
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="17" class="text-center text-muted">No Animals Found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header bg-light"><h5 class="mb-0">Animal Lifecycle History</h5></div>
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Animal</th>
                            <th>Action</th>
                            <th>From Status</th>
                            <th>To Status</th>
                            <th>From Type</th>
                            <th>To Type</th>
                            <th>Notes</th>
                            <th>Changed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $key => $item)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $item->animal->animal_name ?? '-' }} ({{ $item->animal->tag_number ?? '-' }})</td>
                            <td class="text-capitalize">{{ str_replace('_', ' ', $item->action_type) }}</td>
                            <td class="text-capitalize">{{ $item->from_status ?? '-' }}</td>
                            <td class="text-capitalize">{{ $item->to_status ?? '-' }}</td>
                            <td>{{ $item->fromAnimalType->name ?? '-' }}</td>
                            <td>{{ $item->toAnimalType->name ?? '-' }}</td>
                            <td>{{ $item->notes ?? '-' }}</td>
                            <td>{{ optional($item->changed_at)->format('d M Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted">No lifecycle history found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="animalImageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="animalImageTitle">Animal Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" alt="Animal Image" id="animalImagePreview" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/animal/index.js') }}"></script>
@endpush





