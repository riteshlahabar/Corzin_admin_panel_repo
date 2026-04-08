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
                    <p class="text-muted mb-1">Total Dairies</p>
                    <h3 class="mb-0">{{ $summary['total'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success-subtle border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">Active Dairies</p>
                    <h3 class="mb-0">{{ $summary['active'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning-subtle border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">Cities Covered</p>
                    <h3 class="mb-0">{{ $summary['cities'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">Dairy List</h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <input type="text" id="dairySearch" class="form-control" placeholder="Search dairy..." style="width:220px;">
                <button type="button" class="btn btn-light border" onclick="exportTableToPdf('dairyTableExport', 'Dairy List')" title="Download PDF">
                    <i class="fa-solid fa-file-pdf text-danger"></i>
                </button>
                <button type="button" class="btn btn-light border" onclick="exportTableToExcel('dairyTableExport', 'dairy-list')" title="Download Excel">
                    <i class="fa-solid fa-file-excel text-success"></i>
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDairyModal">
                    <i class="fa-solid fa-plus me-1"></i> Add Dairy
                </button>
            </div>
        </div>
    </div>

    <div class="card mt-2">
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table mb-0" id="dairyTableExport">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Farmer</th>
                            <th>Dairy Name</th>
                            <th>GST No.</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>City</th>
                            <th>Taluka</th>
                            <th>District</th>
                            <th>State</th>
                            <th>Pincode</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dairies as $key => $dairy)
                            <tr class="dairy-row" data-search="{{ strtolower(($dairy->farmer->first_name ?? '').' '.$dairy->dairy_name.' '.($dairy->city ?? '').' '.($dairy->district ?? '').' '.($dairy->contact_number ?? '')) }}">
                                <td>{{ $key + 1 }}</td>
                                <td>{{ trim(($dairy->farmer->first_name ?? '').' '.($dairy->farmer->last_name ?? '')) ?: '-' }}</td>
                                <td>{{ $dairy->dairy_name }}</td>
                                <td>{{ $dairy->gst_no ?: '-' }}</td>
                                <td>{{ $dairy->contact_number ?: '-' }}</td>
                                <td>{{ $dairy->address ?: '-' }}</td>
                                <td>{{ $dairy->city ?: '-' }}</td>
                                <td>{{ $dairy->taluka ?: '-' }}</td>
                                <td>{{ $dairy->district ?: '-' }}</td>
                                <td>{{ $dairy->state ?: '-' }}</td>
                                <td>{{ $dairy->pincode ?: '-' }}</td>
                                <td>
                                    <span class="badge {{ $dairy->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ $dairy->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted">No dairies found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addDairyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('farmer.dairy.store') }}">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title text-white">Add Dairy</h5>
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
                            <label class="form-label">Dairy Name</label>
                            <input type="text" name="dairy_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GST No.</label>
                            <input type="text" name="gst_no" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" rows="3" class="form-control"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Taluka</label>
                            <input type="text" name="taluka" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">District</label>
                            <input type="text" name="district" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">State</label>
                            <input type="text" name="state" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pincode</label>
                            <input type="text" name="pincode" class="form-control">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="dairyActive" checked>
                                <label class="form-check-label" for="dairyActive">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Dairy</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/dairy/index.js') }}"></script>
@endpush
