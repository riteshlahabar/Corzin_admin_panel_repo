@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row mb-4 mt-2">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">PAN List</h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <input type="text" id="panSearch" class="form-control" placeholder="Search Farmer / Mobile / PAN / Animal..." style="width:320px;">
                <button type="button" class="btn btn-light border" onclick="exportPanTableToPdf('panTableExport', 'PAN List')" title="Download PDF">
                    <i class="fa-solid fa-file-pdf text-danger"></i>
                </button>
                <button type="button" class="btn btn-light border" onclick="exportPanTableToExcel('panTableExport', 'pan-list')" title="Download Excel">
                    <i class="fa-solid fa-file-excel text-success"></i>
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPanModal">
                    <i class="fa-solid fa-plus me-1"></i> Create PAN
                </button>
            </div>
        </div>
    </div>

    <div class="card mt-2">
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table mb-0" id="panTableExport">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Farmer</th>
                            <th>Mobile</th>
                            <th>PAN Name</th>
                            <th>PAN Type</th>
                            <th>Milk Shifts</th>
                            <th>Animals Count</th>
                            <th>Animals</th>
                            <th class="text-end text-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pans as $key => $pan)
                        @php
                            $farmerName = trim(($pan->farmer->first_name ?? '').' '.($pan->farmer->last_name ?? '')) ?: '-';
                            $farmerMobile = $pan->farmer->mobile ?? '-';
                            $animalNames = $pan->animals->pluck('animal_name')->filter()->implode(', ');
                        @endphp
                        <tr class="pan-row"
                            data-search="{{ strtolower(trim($farmerName.' '.$farmerMobile.' '.($pan->name ?? '').' '.$animalNames.' '.($pan->pan_type ?? '').' '.collect($pan->milk_shifts ?? [])->implode(' '))) }}">
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $farmerName }}</td>
                            <td>{{ $farmerMobile }}</td>
                            <td>{{ $pan->name ?: '-' }}</td>
                            <td class="text-capitalize">{{ str_replace('_', '-', $pan->pan_type ?? 'milking') }}</td>
                            <td>
                                @if(empty($pan->milk_shifts))
                                    <span class="text-muted">-</span>
                                @else
                                    {{ collect($pan->milk_shifts)->implode(', ') }}
                                @endif
                            </td>
                            <td>{{ $pan->animals->count() }}</td>
                            <td>
                                @if($pan->animals->isEmpty())
                                    <span class="text-muted">-</span>
                                @else
                                    {{ $animalNames }}
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="d-inline-flex gap-1 flex-nowrap">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-light border open-transfer-pan"
                                        data-pan-id="{{ $pan->id }}"
                                        data-pan-name="{{ $pan->name }}"
                                        data-farmer-id="{{ $pan->farmer_id }}"
                                        title="Transfer Animal">
                                        <i class="las la-random text-primary fs-18"></i>
                                    </button>
                                    <form method="POST" action="{{ route('farmer.pans.destroy', $pan) }}" class="d-inline m-0" onsubmit="return confirm('Are you sure you want to delete this PAN?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border" title="Delete PAN">
                                            <i class="las la-trash text-danger fs-18"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted">No PAN groups found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createPanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST" action="{{ route('farmer.pans.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Create PAN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Farmer</label>
                        <select name="farmer_id" id="createPanFarmer" class="form-select" required>
                            <option value="">Select Farmer</option>
                            @foreach($farmers as $farmer)
                                <option value="{{ $farmer->id }}">
                                    {{ trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) ?: 'Farmer #'.$farmer->id }} ({{ $farmer->mobile ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">PAN Name</label>
                        <input type="text" name="name" class="form-control" required maxlength="255" placeholder="Enter PAN name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">PAN Type</label>
                        <select name="pan_type" id="createPanType" class="form-select">
                            <option value="milking">Milking PAN</option>
                            <option value="non_milking">Non-Milking PAN</option>
                        </select>
                    </div>
                    <div class="col-md-6" id="createPanShiftWrap">
                        <label class="form-label d-block">Milk Shifts</label>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="milk_shifts[]" value="Morning" id="shiftMorning" checked>
                                <label class="form-check-label" for="shiftMorning">Morning</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="milk_shifts[]" value="Afternoon" id="shiftAfternoon" checked>
                                <label class="form-check-label" for="shiftAfternoon">Afternoon</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="milk_shifts[]" value="Evening" id="shiftEvening" checked>
                                <label class="form-check-label" for="shiftEvening">Evening</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Assign Animals (Optional)</label>
                        <select name="animal_ids[]" id="createPanAnimals" class="form-select" multiple size="8">
                        </select>
                        <small class="text-muted">Only unassigned active animals are shown for selected farmer.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create PAN</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="transferPanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('farmer.pans.transfer') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Transfer Animal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="transferFromPanId">
                <div class="mb-3">
                    <label class="form-label">From PAN</label>
                    <input type="text" id="transferFromPanName" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Select Animal</label>
                    <select name="animal_id" id="transferAnimalId" class="form-select" required></select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Transfer To PAN</label>
                    <select name="to_pan_id" id="transferToPanId" class="form-select" required></select>
                </div>
                <div class="mb-0">
                    <label class="form-label">Notes (Optional)</label>
                    <input type="text" name="notes" class="form-control" maxlength="255" placeholder="Transfer notes">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Transfer</button>
            </div>
        </form>
    </div>
</div>

@php
    $panAnimalsMap = $pans->mapWithKeys(function ($pan) {
        return [$pan->id => $pan->animals->map(function ($animal) {
            return [
                'id' => $animal->id,
                'name' => $animal->animal_name,
                'tag' => $animal->tag_number,
                'type' => optional($animal->animalType)->name,
            ];
        })->values()];
    });
    $panDestinationsMap = $pans->groupBy('farmer_id')->map(function ($rows) {
        return $rows->map(fn ($pan) => ['id' => $pan->id, 'name' => $pan->name])->values();
    });
    $assignableAnimalsMap = $assignableAnimals->groupBy('farmer_id')->map(function ($rows) {
        return $rows->map(function ($animal) {
            return [
                'id' => $animal->id,
                'name' => $animal->animal_name,
                'tag' => $animal->tag_number,
                'type' => optional($animal->animalType)->name,
            ];
        })->values();
    });
@endphp

<script>
    window.panAnimalsMap = @json($panAnimalsMap);
    window.panDestinationsMap = @json($panDestinationsMap);
    window.assignableAnimalsMap = @json($assignableAnimalsMap);
</script>
@endsection

@push('scripts')
<script src="{{ asset('js/animal/pan_list.js') }}"></script>
@endpush
