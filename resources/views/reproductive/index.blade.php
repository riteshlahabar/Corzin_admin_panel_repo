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
                    <p class="text-muted mb-1">Total Records</p>
                    <h3 class="mb-0">{{ $summary['total'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success-subtle border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">Pregnancy Confirmed</p>
                    <h3 class="mb-0">{{ $summary['pregnant'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning-subtle border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">Calving Recorded</p>
                    <h3 class="mb-0">{{ $summary['calving'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">Reproductive Register</h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <input type="text" id="reproductiveSearch" class="form-control" placeholder="Search record..." style="width:220px;">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReproductiveModal">
                    <i class="fa-solid fa-plus me-1"></i> Add Record
                </button>
            </div>
        </div>
    </div>

    <div class="card mt-2">
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Farmer</th>
                            <th>Animal</th>
                            <th>Lactation</th>
                            <th>AI Date</th>
                            <th>Breed Name</th>
                            <th>Pregnancy</th>
                            <th>Calving Date</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $key => $record)
                            <tr class="reproductive-row" data-search="{{ strtolower(($record->animal->farmer->first_name ?? '').' '.($record->animal->animal_name ?? '').' '.($record->breed_name ?? '')) }}">
                                <td>{{ $key + 1 }}</td>
                                <td>{{ trim(($record->animal->farmer->first_name ?? '').' '.($record->animal->farmer->last_name ?? '')) ?: '-' }}</td>
                                <td>{{ $record->animal->animal_name ?? '-' }}</td>
                                <td>{{ $record->lactation_number ?? 'N/A' }}</td>
                                <td>{{ $record->ai_date ? $record->ai_date->format('d M Y') : '-' }}</td>
                                <td>{{ $record->breed_name ?: '-' }}</td>
                                <td>
                                    <span class="badge {{ $record->pregnancy_confirmation ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ $record->pregnancy_confirmation ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td>{{ $record->calving_date ? $record->calving_date->format('d M Y') : '-' }}</td>
                                <td>{{ $record->notes ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No reproductive records found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addReproductiveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('reproductive.store') }}">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title text-white">Add Reproductive Record</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
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
                            <label class="form-label">Lactation Number</label>
                            <input type="number" name="lactation_number" class="form-control" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">AI Date</label>
                            <input type="date" name="ai_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Breed Name</label>
                            <input type="text" name="breed_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Calving Date</label>
                            <input type="date" name="calving_date" class="form-control">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="pregnancy_confirmation" value="1" id="pregnancyConfirmation">
                                <label class="form-check-label" for="pregnancyConfirmation">Pregnancy Confirmed</label>
                            </div>
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
@endsection

@push('scripts')
<script>
document.getElementById('reproductiveSearch')?.addEventListener('input', function () {
    const value = this.value.toLowerCase().trim();
    document.querySelectorAll('.reproductive-row').forEach((row) => {
        const haystack = row.dataset.search || '';
        row.style.display = haystack.includes(value) ? '' : 'none';
    });
});
</script>
@endpush
