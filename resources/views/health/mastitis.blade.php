@extends('layouts.app')

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
                    <option value="quarter">Quarter</option>
                    <option value="clinical-type">Clinical Type</option>
                    <option value="cmt-score">CMT Score</option>
                    <option value="scc-count">SCC Count</option>
                    <option value="date-text">Date</option>
                    <option value="follow-up-date">Follow-up Date</option>
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
                            <th>Quarter</th>
                            <th>Clinical Type</th>
                            <th>CMT Score</th>
                            <th>SCC Count</th>
                            <th>Date</th>
                            <th>Follow-up Date</th>
                            <th>Notes</th>
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
                                data-quarter="{{ strtolower($row->quarter ?? '') }}"
                                data-clinical-type="{{ strtolower($row->clinical_type ?? '') }}"
                                data-cmt-score="{{ strtolower($row->cmt_score ?? '') }}"
                                data-scc-count="{{ strtolower($row->scc_count !== null ? number_format((float) $row->scc_count, 2) : '') }}"
                                data-date-text="{{ strtolower(optional($row->date)->format('d-m-Y') ?? '') }}"
                                data-follow-up-date="{{ strtolower(optional($row->follow_up_date)->format('d-m-Y') ?? '') }}"
                                data-notes="{{ strtolower($row->notes ?? '') }}"
                                data-date="{{ optional($row->date)->format('Y-m-d') }}"
                            >
                                <td>{{ $key + 1 }}</td>
                                <td>{{ trim(($row->farmer->first_name ?? '').' '.($row->farmer->last_name ?? '')) ?: '-' }}</td>
                                <td>{{ $row->animal->animal_name ?? '-' }}</td>
                                <td>{{ $row->animal->tag_number ?? '-' }}</td>
                                <td>{{ $row->test_result }}</td>
                                <td>{{ $row->treatment }}</td>
                                <td>{{ $row->recovery_status }}</td>
                                <td>{{ $row->quarter ?: '-' }}</td>
                                <td>{{ $row->clinical_type ?: '-' }}</td>
                                <td>{{ $row->cmt_score ?: '-' }}</td>
                                <td>{{ $row->scc_count !== null ? number_format((float) $row->scc_count, 2) : '-' }}</td>
                                <td>{{ optional($row->date)->format('d-m-Y') }}</td>
                                <td>{{ optional($row->follow_up_date)->format('d-m-Y') ?: '-' }}</td>
                                <td>{{ $row->notes ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="text-center text-muted">No mastitis records found</td>
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
                            <select name="farmer_id" class="form-select" required>
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
                            <select name="animal_id" class="form-select" required>
                                <option value="">Select animal</option>
                                @foreach($animals as $animal)
                                    <option value="{{ $animal->id }}">{{ $animal->animal_name }} - {{ $animal->tag_number }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Test Result</label>
                            <select name="test_result" class="form-select" required>
                                <option value="Positive">Positive</option>
                                <option value="Negative">Negative</option>
                                <option value="Suspected">Suspected</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Treatment</label>
                            <input type="text" name="treatment" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Recovery Status</label>
                            <select name="recovery_status" class="form-select" required>
                                <option value="Under Treatment">Under Treatment</option>
                                <option value="Recovered">Recovered</option>
                                <option value="Not Recovered">Not Recovered</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Quarter</label>
                            <select name="quarter" class="form-select">
                                <option value="">Select</option>
                                <option value="LF">LF</option>
                                <option value="RF">RF</option>
                                <option value="LR">LR</option>
                                <option value="RR">RR</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Clinical Type</label>
                            <select name="clinical_type" class="form-select">
                                <option value="">Select</option>
                                <option value="Clinical">Clinical</option>
                                <option value="Subclinical">Subclinical</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CMT Score</label>
                            <select name="cmt_score" class="form-select">
                                <option value="">Select</option>
                                <option value="Negative">Negative</option>
                                <option value="Trace">Trace</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">SCC Count</label>
                            <input type="number" step="0.01" min="0" name="scc_count" class="form-control" placeholder="cells/ml">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Follow-up Date</label>
                            <input type="date" name="follow_up_date" class="form-control">
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
<script src="{{ asset('js/health/index.js') }}"></script>
@endpush
