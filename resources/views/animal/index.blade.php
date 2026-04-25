@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-4 mt-2">
        <div class="col-md-6 col-lg-3"><div class="card bg-warning-subtle"><div class="card-body text-center"><h5 class="fw-bold mb-1" style="font-size:18px;">Calf</h5><h2 class="fw-bold mb-0">{{ $counts['calf'] }}</h2></div></div></div>
        <div class="col-md-6 col-lg-3"><div class="card bg-info-subtle"><div class="card-body text-center"><h5 class="fw-bold mb-1" style="font-size:18px;">Heifer</h5><h2 class="fw-bold mb-0">{{ $counts['heifer'] }}</h2></div></div></div>
        <div class="col-md-6 col-lg-3"><div class="card bg-secondary-subtle"><div class="card-body text-center"><h5 class="fw-bold mb-1" style="font-size:18px;">Dry Cow</h5><h2 class="fw-bold mb-0">{{ $counts['dry'] }}</h2></div></div></div>
        <div class="col-md-6 col-lg-3"><div class="card bg-success-subtle"><div class="card-body text-center"><h5 class="fw-bold mb-1" style="font-size:18px;">Milking Cow</h5><h2 class="fw-bold mb-0">{{ $counts['milking'] }}</h2></div></div></div>
    </div>

    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">Animal List</h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="btn-group" role="group">
                    @foreach($animalTypes as $type)
                        <input type="checkbox" class="btn-check animal-filter" id="type{{ $type->id }}" value="{{ $type->id }}" checked>
                        <label class="btn btn-outline-primary active" for="type{{ $type->id }}">{{ $type->name }}</label>
                    @endforeach
                </div>
                <input type="text" id="animalSearch" class="form-control" placeholder="Search Farmer..." style="width:220px;">
                <div class="input-group" style="width:260px;">
                    <input type="date" id="startDate" class="form-control">
                    <span class="input-group-text">to</span>
                    <input type="date" id="endDate" class="form-control">
                </div>
                <button type="button" class="btn btn-light border" onclick="exportTableToPdf('animalTableExport', 'Animal List')" title="Download PDF">
                    <i class="fa-solid fa-file-pdf text-danger"></i>
                </button>
                <button type="button" class="btn btn-light border" onclick="exportTableToExcel('animalTableExport', 'animal-list')" title="Download Excel">
                    <i class="fa-solid fa-file-excel text-success"></i>
                </button>
                <a href="{{ route('animal.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus me-1"></i> Add Animal
                </a>
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
                            <th>Type</th>
                            <th>PAN</th>
                            <th>Gender</th>
                            <th>Birth Date</th>
                            <th>Age</th>
                            <th>Weight</th>
                            <th>Image</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($animals as $key => $animal)
                        <tr class="animal-row"
                            data-type="{{ $animal->animal_type_id }}"
                            data-search="{{ strtolower(trim(($animal->farmer->first_name ?? '').' '.($animal->farmer->last_name ?? '').' '.($animal->animal_name ?? '').' '.($animal->tag_number ?? '').' '.($animal->unique_id ?? ''))) }}"
                            data-date="{{ optional($animal->created_at)->format('Y-m-d') }}">
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $animal->unique_id }}</td>
                            <td>{{ trim(($animal->farmer->first_name ?? '').' '.($animal->farmer->last_name ?? '')) ?: '-' }}</td>
                            <td>{{ $animal->animal_name }}</td>
                            <td>{{ $animal->tag_number }}</td>
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
                            <td class="text-end">
                                <a href="{{ route('animal.edit', $animal) }}" class="btn btn-sm btn-light border me-1" title="Edit Animal">
                                    <i class="las la-pen text-primary fs-18"></i>
                                </a>
                                <form method="POST" action="{{ route('animal.toggle', $animal) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $animal->is_active ? 'btn-success' : 'btn-danger' }}" title="{{ $animal->is_active ? 'Set Inactive' : 'Set Active' }}">
                                        <i class="las {{ $animal->is_active ? 'la-check-circle' : 'la-times-circle' }} fs-18"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="14" class="text-center text-muted">No Animals Found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header bg-light"><h5 class="mb-0">PAN List</h5></div>
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Farmer</th>
                            <th>PAN Name</th>
                            <th>Animals Count</th>
                            <th>Animals</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pans as $key => $pan)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>{{ trim(($pan->farmer->first_name ?? '').' '.($pan->farmer->last_name ?? '')) ?: '-' }}</td>
                            <td>{{ $pan->name ?: '-' }}</td>
                            <td>{{ $pan->animals->count() }}</td>
                            <td>
                                @if($pan->animals->isEmpty())
                                    <span class="text-muted">-</span>
                                @else
                                    {{ $pan->animals->pluck('animal_name')->filter()->implode(', ') }}
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">No PAN groups found</td></tr>
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



