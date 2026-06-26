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
                <select id="dairySearchField" class="form-select" style="width:190px;">
                    <option value="all">All Columns</option>
                    <option value="farmer">Farmer</option>
                    <option value="dairy-name">Dairy Name</option>
                    <option value="gst-no">GST No.</option>
                    <option value="contact">Contact</option>
                    <option value="address">Address</option>
                    <option value="city">City</option>
                    <option value="taluka">Taluka</option>
                    <option value="district">District</option>
                    <option value="state">State</option>
                    <option value="pincode">Pincode</option>
                    <option value="status">Status</option>
                </select>
                <input type="text" id="dairySearch" class="form-control" placeholder="Search selected field..." style="width:220px;">
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
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dairies as $key => $dairy)
                            <tr class="dairy-row"
                                data-all="{{ strtolower(trim(($dairy->farmer->first_name ?? '').' '.($dairy->farmer->last_name ?? '').' '.($dairy->dairy_name ?? '').' '.($dairy->gst_no ?? '').' '.($dairy->contact_number ?? '').' '.($dairy->address ?? '').' '.($dairy->city ?? '').' '.($dairy->taluka ?? '').' '.($dairy->district ?? '').' '.($dairy->state ?? '').' '.($dairy->pincode ?? '').' '.($dairy->is_active ? 'active' : 'inactive'))) }}"
                                data-farmer="{{ strtolower(trim(($dairy->farmer->first_name ?? '').' '.($dairy->farmer->last_name ?? ''))) }}"
                                data-dairy-name="{{ strtolower($dairy->dairy_name ?? '') }}"
                                data-gst-no="{{ strtolower($dairy->gst_no ?? '') }}"
                                data-contact="{{ strtolower($dairy->contact_number ?? '') }}"
                                data-address="{{ strtolower($dairy->address ?? '') }}"
                                data-city="{{ strtolower($dairy->city ?? '') }}"
                                data-taluka="{{ strtolower($dairy->taluka ?? '') }}"
                                data-district="{{ strtolower($dairy->district ?? '') }}"
                                data-state="{{ strtolower($dairy->state ?? '') }}"
                                data-pincode="{{ strtolower($dairy->pincode ?? '') }}"
                                data-status="{{ $dairy->is_active ? 'active' : 'inactive' }}">
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
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        @perm('dairy.edit')
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editDairyModal{{ $dairy->id }}">
                                            Edit
                                        </button>
                                        @endperm
                                        @perm('dairy.delete')
                                        <form method="POST" action="{{ route('farmer.dairy.destroy', $dairy) }}" onsubmit="return confirm('Delete this dairy?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                        @endperm
                                    </div>
                                </td>
                            </tr>

                            @perm('dairy.edit')
                            <div class="modal fade" id="editDairyModal{{ $dairy->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('farmer.dairy.update', $dairy) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title text-white">Edit Dairy</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Farmer</label>
                                                        <select name="farmer_id" class="form-select" required>
                                                            <option value="">Select farmer</option>
                                                            @foreach($farmers as $farmer)
                                                                <option value="{{ $farmer->id }}" {{ (int) $dairy->farmer_id === (int) $farmer->id ? 'selected' : '' }}>
                                                                    {{ trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) }} - {{ $farmer->mobile }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Dairy Name</label>
                                                        <input type="text" name="dairy_name" class="form-control" value="{{ $dairy->dairy_name }}" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">GST No.</label>
                                                        <input type="text" name="gst_no" class="form-control" value="{{ $dairy->gst_no }}">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Contact Number</label>
                                                        <input type="text" name="contact_number" class="form-control" value="{{ $dairy->contact_number }}">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Address</label>
                                                        <textarea name="address" rows="3" class="form-control">{{ $dairy->address }}</textarea>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">City</label>
                                                        <input type="text" name="city" class="form-control" value="{{ $dairy->city }}">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Taluka</label>
                                                        <input type="text" name="taluka" class="form-control" value="{{ $dairy->taluka }}">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">District</label>
                                                        <input type="text" name="district" class="form-control" value="{{ $dairy->district }}">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">State</label>
                                                        <input type="text" name="state" class="form-control" value="{{ $dairy->state }}">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Pincode</label>
                                                        <input type="text" name="pincode" class="form-control" value="{{ $dairy->pincode }}">
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editDairyActive{{ $dairy->id }}" {{ $dairy->is_active ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="editDairyActive{{ $dairy->id }}">Active</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Update Dairy</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endperm
                        @empty
                            <tr>
                                <td colspan="13" class="text-center text-muted">No dairies found</td>
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
