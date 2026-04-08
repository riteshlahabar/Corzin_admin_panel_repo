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
                <input type="text" id="healthSearch" class="form-control" placeholder="Search medical..." style="width:220px;">
                <div class="input-group" style="width:260px;">
                    <input type="date" id="startDate" class="form-control">
                    <span class="input-group-text">to</span>
                    <input type="date" id="endDate" class="form-control">
                </div>
                <button type="button" class="btn btn-light border" onclick="exportTableToPdf('healthTableExport', '{{ $title }}')" title="Download PDF"><i class="fa-solid fa-file-pdf text-danger"></i></button>
                <button type="button" class="btn btn-light border" onclick="exportTableToExcel('healthTableExport', 'medical-records')" title="Download Excel"><i class="fa-solid fa-file-excel text-success"></i></button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#medicalModal"><i class="fa-solid fa-plus me-1"></i> Add Medical</button>
            </div>
        </div>
    </div>

    <div class="card mt-2">
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table mb-0" id="healthTableExport">
                    <thead class="table-light">
                        <tr><th>#</th><th>Farmer</th><th>Animal</th><th>Tag</th><th>Medicine Name</th><th>Dose</th><th>Disease</th><th>Date</th><th>Notes</th></tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $key => $row)
                        <tr class="health-row" data-search="{{ strtolower(trim(($row->farmer->first_name ?? '').' '.($row->farmer->last_name ?? '').' '.($row->animal->animal_name ?? '').' '.($row->animal->tag_number ?? '').' '.($row->medicine_name ?? '').' '.($row->disease ?? ''))) }}" data-date="{{ optional($row->date)->format('Y-m-d') }}">
                            <td>{{ $key + 1 }}</td>
                            <td>{{ trim(($row->farmer->first_name ?? '').' '.($row->farmer->last_name ?? '')) ?: '-' }}</td>
                            <td>{{ $row->animal->animal_name ?? '-' }}</td>
                            <td>{{ $row->animal->tag_number ?? '-' }}</td>
                            <td>{{ $row->medicine_name }}</td>
                            <td>{{ $row->dose }}</td>
                            <td>{{ $row->disease }}</td>
                            <td>{{ optional($row->date)->format('d-m-Y') }}</td>
                            <td>{{ $row->notes ?: '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted">No medical records found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="medicalModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('health.medical.store') }}">
        @csrf
        <div class="modal-header bg-success text-white"><h5 class="modal-title">Add Medical Record</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div class="row g-3">
          <div class="col-md-6"><label class="form-label">Farmer</label><select name="farmer_id" class="form-select" required><option value="">Select farmer</option>@foreach($farmers as $farmer)<option value="{{ $farmer->id }}">{{ trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) }} - {{ $farmer->mobile }}</option>@endforeach</select></div>
          <div class="col-md-6"><label class="form-label">Animal</label><select name="animal_id" class="form-select" required><option value="">Select animal</option>@foreach($animals as $animal)<option value="{{ $animal->id }}">{{ $animal->animal_name }} - {{ $animal->tag_number }}</option>@endforeach</select></div>
          <div class="col-md-6"><label class="form-label">Medicine Name</label><input type="text" name="medicine_name" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label">Dose</label><input type="text" name="dose" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label">Disease</label><input type="text" name="disease" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label">Date</label><input type="date" name="date" class="form-control" required></div>
          <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" rows="3" class="form-control"></textarea></div>
        </div></div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save Record</button></div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/health/index.js') }}"></script>
@endpush
