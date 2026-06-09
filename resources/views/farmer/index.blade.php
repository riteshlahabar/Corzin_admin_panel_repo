@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row mt-4 mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h4 class="page-title mb-0">Farmer List</h4>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <select id="farmerSearchField" class="form-select" style="width:190px;">
                        <option value="all">All Columns</option>
                        <option value="mobile">Mobile</option>
                        <option value="first-name">First Name</option>
                        <option value="middle-name">Middle Name</option>
                        <option value="last-name">Last Name</option>
                        <option value="village">Village</option>
                        <option value="city">City</option>
                        <option value="taluka">Taluka</option>
                        <option value="district">District</option>
                        <option value="state">State</option>
                        <option value="pincode">Pincode</option>
                        <option value="status">Status</option>
                        <option value="last-app-activity">Last App Activity</option>
                    </select>
                    <input type="text" id="farmerSearch" class="form-control" placeholder="Search selected field..." style="width:220px;">

                    <div class="input-group" style="width:260px;">
                        <input type="date" id="startDate" class="form-control">
                        <span class="input-group-text">to</span>
                        <input type="date" id="endDate" class="form-control">
                    </div>

                    <button type="button" class="btn btn-light border" onclick="exportTableToPdf('farmerTableExport', 'Farmer List')" title="Download PDF">
                        <i class="fa-solid fa-file-pdf text-danger"></i>
                    </button>

                    <button type="button" class="btn btn-light border" onclick="exportTableToExcel('farmerTableExport', 'farmer-list')" title="Download Excel">
                        <i class="fa-solid fa-file-excel text-success"></i>
                    </button>

                    <a href="{{ route('farmer.create') }}" class="btn btn-primary">
                        <i class="fa-solid fa-plus me-1"></i> Add Farmer
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-2">
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table mb-0" id="farmerTableExport">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Mobile</th>
                            <th>First Name</th>
                            <th>Middle Name</th>
                            <th>Last Name</th>
                            <th>Village</th>
                            <th>City</th>
                            <th>Taluka</th>
                            <th>District</th>
                            <th>State</th>
                            <th>Pincode</th>
                            <th>Status</th>
                            <th>Last App activity</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="farmerTable">
                        @forelse($farmers as $key => $farmer)
                        <tr class="farmer-row"
                            data-all="{{ strtolower(trim(($farmer->first_name ?? '').' '.($farmer->middle_name ?? '').' '.($farmer->last_name ?? '').' '.($farmer->mobile ?? '').' '.($farmer->village ?? '').' '.($farmer->city ?? '').' '.($farmer->taluka ?? '').' '.($farmer->district ?? '').' '.($farmer->state ?? '').' '.($farmer->pincode ?? '').' '.($farmer->is_active ? 'active' : 'inactive').' '.($farmer->active_session_at ? $farmer->active_session_at->format('d M Y h:i A') : 'never'))) }}"
                            data-mobile="{{ strtolower($farmer->mobile ?? '') }}"
                            data-first-name="{{ strtolower($farmer->first_name ?? '') }}"
                            data-middle-name="{{ strtolower($farmer->middle_name ?? '') }}"
                            data-last-name="{{ strtolower($farmer->last_name ?? '') }}"
                            data-village="{{ strtolower($farmer->village ?? '') }}"
                            data-city="{{ strtolower($farmer->city ?? '') }}"
                            data-taluka="{{ strtolower($farmer->taluka ?? '') }}"
                            data-district="{{ strtolower($farmer->district ?? '') }}"
                            data-state="{{ strtolower($farmer->state ?? '') }}"
                            data-pincode="{{ strtolower($farmer->pincode ?? '') }}"
                            data-status="{{ $farmer->is_active ? 'active' : 'inactive' }}"
                            data-last-app-activity="{{ strtolower($farmer->active_session_at ? $farmer->active_session_at->format('d M Y h:i A') : 'never') }}"
                            data-date="{{ optional($farmer->created_at)->format('Y-m-d') }}">
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $farmer->mobile }}</td>
                            <td>{{ $farmer->first_name }}</td>
                            <td>{{ $farmer->middle_name ?: '-' }}</td>
                            <td>{{ $farmer->last_name }}</td>
                            <td>{{ $farmer->village }}</td>
                            <td>{{ $farmer->city ?: '-' }}</td>
                            <td>{{ $farmer->taluka ?: '-' }}</td>
                            <td>{{ $farmer->district ?: '-' }}</td>
                            <td>{{ $farmer->state ?: '-' }}</td>
                            <td>{{ $farmer->pincode ?: '-' }}</td>
                            <td>
                                <span class="badge {{ $farmer->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                    {{ $farmer->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                @if($farmer->active_session_at)
                                    <div>{{ $farmer->active_session_at->format('d M Y') }}</div>
                                    <small class="text-muted">{{ $farmer->active_session_at->format('h:i A') }}</small>
                                @else
                                    <span class="text-muted">Never</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('farmer.edit', $farmer) }}" class="btn btn-sm btn-light border me-1" title="Edit Farmer">
                                    <i class="las la-pen text-primary fs-18"></i>
                                </a>
                                <form method="POST" action="{{ route('farmer.toggle', $farmer) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $farmer->is_active ? 'btn-success' : 'btn-danger' }}" title="{{ $farmer->is_active ? 'Set Inactive' : 'Set Active' }}">
                                        <i class="las {{ $farmer->is_active ? 'la-check-circle' : 'la-times-circle' }} fs-18"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="14" class="text-center text-muted">No Farmers Found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/farmer/index.js') }}"></script>
@endpush

