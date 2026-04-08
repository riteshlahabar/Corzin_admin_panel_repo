@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-4 mt-2">
        <div class="col-md-4">
            <div class="card bg-primary-subtle border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">Total Entries</p>
                    <h3 class="mb-0">{{ $summary['total'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success-subtle border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">Today Feeding</p>
                    <h3 class="mb-0">{{ number_format($summary['today'], 2) }} Kg</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning-subtle border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">Feed Types</p>
                    <h3 class="mb-0">{{ $summary['types'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">Feeding Management</h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <input type="text" id="feedingSearch" class="form-control" placeholder="Search farmer, animal, feed type..." style="width:260px;">
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
                            <th>Feed Type</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Time</th>
                            <th>Date</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $key => $record)
                        <tr class="feeding-row"
                            data-search="{{ strtolower(trim(($record->farmer->first_name ?? '').' '.($record->farmer->last_name ?? '').' '.($record->animal->animal_name ?? '').' '.($record->feedType->name ?? ''))) }}"
                            data-date="{{ optional($record->date)->format('Y-m-d') }}">
                            <td>{{ $key + 1 }}</td>
                            <td>{{ trim(($record->farmer->first_name ?? '').' '.($record->farmer->last_name ?? '')) ?: '-' }}</td>
                            <td>{{ $record->animal->animal_name ?? '-' }}{{ !empty($record->animal->tag_number) ? ' - Tag '.$record->animal->tag_number : '' }}</td>
                            <td>{{ $record->feedType->name ?? '-' }}</td>
                            <td>{{ number_format($record->quantity, 2) }}</td>
                            <td>{{ $record->unit }}</td>
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
                            <select name="farmer_id" class="form-select" required>
                                <option value="">Select farmer</option>
                                @foreach($farmers as $farmer)
                                    <option value="{{ $farmer->id }}">{{ trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) }} - {{ $farmer->mobile }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Animal</label>
                            <select name="animal_id" class="form-select" required>
                                <option value="">Select animal</option>
                                @foreach($animals as $animal)
                                    <option value="{{ $animal->id }}">{{ $animal->animal_name }} - {{ $animal->tag_number }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Feed Type</label>
                            <select name="feed_type_id" class="form-select" required>
                                <option value="">Select feed type</option>
                                @foreach($feedTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" step="0.01" min="0.01" name="quantity" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unit</label>
                            <select name="unit" class="form-select" required>
                                <option value="Kg">Kg</option>
                                <option value="Gram">Gram</option>
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
@endpush
