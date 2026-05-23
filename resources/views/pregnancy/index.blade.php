@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row g-3 mb-4 mt-2">
        <div class="col-md-3">
            <div class="card bg-primary-subtle border-0"><div class="card-body"><p class="text-muted mb-1">Total Records</p><h3 class="mb-0">{{ $summary['total'] }}</h3></div></div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success-subtle border-0"><div class="card-body"><p class="text-muted mb-1">Current</p><h3 class="mb-0">{{ $summary['current'] }}</h3></div></div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning-subtle border-0"><div class="card-body"><p class="text-muted mb-1">Pregnant</p><h3 class="mb-0">{{ $summary['pregnant'] }}</h3></div></div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info-subtle border-0"><div class="card-body"><p class="text-muted mb-1">Calved</p><h3 class="mb-0">{{ $summary['calved'] }}</h3></div></div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">Cow Pregnancy / Reproduction</h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <input type="text" id="pregnancySearch" class="form-control" placeholder="Search farmer, cow, tag, status..." style="width:280px;">
                <button type="button" class="btn btn-light border" onclick="exportTableToPdf('pregnancyTableExport', 'Pregnancy Records')" title="Download PDF">
                    <i class="fa-solid fa-file-pdf text-danger"></i>
                </button>
                <button type="button" class="btn btn-light border" onclick="exportTableToExcel('pregnancyTableExport', 'pregnancy-records')" title="Download Excel">
                    <i class="fa-solid fa-file-excel text-success"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table mb-0" id="pregnancyTableExport">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Farmer</th>
                            <th>Cow</th>
                            <th>Pregnancy No</th>
                            <th>Service No</th>
                            <th>AI Date</th>
                            <th>Check Due</th>
                            <th>Expected Calving</th>
                            <th>Result</th>
                            <th>Status</th>
                            <th>Current</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($records as $key => $record)
                        @php
                            $farmerName = trim(($record->farmer->first_name ?? '').' '.($record->farmer->last_name ?? ''));
                            $animalName = trim(($record->animal->animal_name ?? '').' '.(!empty($record->animal->tag_number) ? '(Tag: '.$record->animal->tag_number.')' : ''));
                        @endphp
                        <tr class="pregnancy-row" data-search="{{ strtolower($farmerName.' '.$animalName.' '.$record->status.' '.$record->pregnancy_result.' '.$record->doctor_name) }}">
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $farmerName ?: '-' }}</td>
                            <td>{{ $animalName ?: '-' }}</td>
                            <td>{{ $record->pregnancy_no }}</td>
                            <td>{{ $record->service_no }}</td>
                            <td>{{ optional($record->ai_date)->format('d-m-Y') ?: '-' }}</td>
                            <td>{{ optional($record->pregnancy_check_due_date)->format('d-m-Y') ?: '-' }}</td>
                            <td>{{ optional($record->expected_calving_date)->format('d-m-Y') ?: '-' }}</td>
                            <td><span class="badge bg-light text-dark">{{ str_replace('_', ' ', ucfirst($record->pregnancy_result)) }}</span></td>
                            <td><span class="badge bg-success-subtle text-success">{{ str_replace('_', ' ', ucfirst($record->status)) }}</span></td>
                            <td>{{ $record->is_current ? 'Yes' : 'No' }}</td>
                            <td>{{ $record->notes ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="text-center text-muted py-4">No pregnancy records found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('pregnancySearch');
    if (!input) return;
    input.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('.pregnancy-row').forEach(row => {
            row.style.display = row.dataset.search.includes(q) ? '' : 'none';
        });
    });
});
</script>
@endsection
