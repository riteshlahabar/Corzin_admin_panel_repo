@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4 mt-2">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">{{ $title }}</h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <input type="text" id="lifecycleSearch" class="form-control" placeholder="Search animal..." style="width:220px;">
                <div class="input-group" style="width:260px;">
                    <input type="date" id="startDate" class="form-control">
                    <span class="input-group-text">to</span>
                    <input type="date" id="endDate" class="form-control">
                </div>
                <button type="button" class="btn btn-light border" onclick="exportTableToPdf('lifecycleTableExport', '{{ $title }}')" title="Download PDF">
                    <i class="fa-solid fa-file-pdf text-danger"></i>
                </button>
                <button type="button" class="btn btn-light border" onclick="exportTableToExcel('lifecycleTableExport', '{{ strtolower(str_replace(' ', '-', $title)) }}')" title="Download Excel">
                    <i class="fa-solid fa-file-excel text-success"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="card mt-2">
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table mb-0" id="lifecycleTableExport">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Farmer</th>
                            <th>Animal Name</th>
                            <th>Tag Number</th>
                            <th>Unique ID</th>
                            <th>Pen</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Weight</th>
                            <th>{{ $section === 'sold' ? 'Sold At' : ($section === 'death' ? 'Death At' : 'Created At') }}</th>
                            @if($section === 'active')
                                <th>Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $key => $animal)
                        @php
                            $dateValue = $section === 'sold' ? optional($animal->sold_at)->format('Y-m-d') : ($section === 'death' ? optional($animal->death_at)->format('Y-m-d') : optional($animal->created_at)->format('Y-m-d'));
                            $displayDate = $section === 'sold' ? optional($animal->sold_at)->format('d-m-Y H:i') : ($section === 'death' ? optional($animal->death_at)->format('d-m-Y H:i') : optional($animal->created_at)->format('d-m-Y'));
                        @endphp
                        <tr class="lifecycle-row"
                            data-search="{{ strtolower(trim(($animal->farmer->first_name ?? '').' '.($animal->farmer->last_name ?? '').' '.($animal->animal_name ?? '').' '.($animal->tag_number ?? '').' '.($animal->unique_id ?? '').' '.($animal->animalType->name ?? ''))) }}"
                            data-date="{{ $dateValue }}">
                            <td>{{ $key + 1 }}</td>
                            <td>{{ trim(($animal->farmer->first_name ?? '').' '.($animal->farmer->last_name ?? '')) ?: '-' }}</td>
                            <td>{{ $animal->animal_name ?: '-' }}</td>
                            <td>{{ $animal->tag_number ?: '-' }}</td>
                            <td>{{ $animal->unique_id ?: '-' }}</td>
                            <td>{{ $animal->pan->name ?? '-' }}</td>
                            <td>{{ $animal->calculated_age ?: '-' }}</td>
                            <td>{{ $animal->gender ?: '-' }}</td>
                            <td>{{ $animal->weight ? $animal->weight.' kg' : '-' }}</td>
                            <td>{{ $displayDate ?: '-' }}</td>
                            @if($section === 'active')
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                @if($animal->is_for_sale)
                                                    <form method="POST" action="{{ route('animal.lifecycle.active.cancel_selling', $animal) }}" onsubmit="return confirm('Cancel selling for {{ $animal->animal_name ?: 'this animal' }}?');">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item">
                                                            Cancel Selling {{ $animal->animal_name ? '('.$animal->animal_name.')' : '' }}
                                                        </button>
                                                    </form>
                                                @else
                                                    <button
                                                        type="button"
                                                        class="dropdown-item js-open-sell-modal"
                                                        data-animal-id="{{ $animal->id }}"
                                                        data-animal-name="{{ $animal->animal_name ?: '-' }}"
                                                        data-tag-number="{{ $animal->tag_number ?: '-' }}"
                                                    >
                                                        Sell Animal {{ $animal->animal_name ? '('.$animal->animal_name.')' : '' }}
                                                    </button>
                                                @endif
                                            </li>
                                            <li>
                                                <form method="POST" action="{{ route('animal.lifecycle.active.sold', $animal) }}" onsubmit="return confirm('Mark {{ $animal->animal_name ?: 'this animal' }} as sold?');">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">Sold</button>
                                                </form>
                                            </li>
                                            <li>
                                                <form method="POST" action="{{ route('animal.lifecycle.active.death', $animal) }}" onsubmit="return confirm('Record death for {{ $animal->animal_name ?: 'this animal' }}?');">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">Death</button>
                                                </form>
                                            </li>
                                            <li>
                                                <button
                                                    type="button"
                                                    class="dropdown-item js-open-transfer-modal"
                                                    data-animal-id="{{ $animal->id }}"
                                                    data-animal-name="{{ $animal->animal_name ?: '-' }}"
                                                    data-tag-number="{{ $animal->tag_number ?: '-' }}"
                                                    data-farmer-id="{{ $animal->farmer_id }}"
                                                    data-from-pan-id="{{ $animal->pan_id }}"
                                                    data-from-pan-name="{{ $animal->pan->name ?? '-' }}"
                                                >
                                                    Pan Transfer
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $section === 'active' ? 11 : 10 }}" class="text-center text-muted">No records found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if($section === 'active')
<div class="modal fade" id="sellAnimalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="sellAnimalForm">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Sell Animal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border py-2 px-3 mb-3">
                        <span class="fw-semibold" id="sellAnimalNameText">-</span>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Selling Price</label>
                        <input type="number" step="0.01" min="1" name="selling_price" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Sell Animal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="transferPanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="transferAnimalForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Transfer Animal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">From Pen</label>
                    <input type="text" id="transferFromPanName" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Animal</label>
                    <input type="text" id="transferAnimalName" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Transfer To Pen</label>
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

<script>
    window.activePanDestinationsMap = @json($panDestinationsMap ?? collect());
</script>
@endif
@endsection

@push('scripts')
<script src="{{ asset('js/animal_lifecycle/index.js') }}"></script>
@if($section === 'active')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sellAnimalForm = document.getElementById('sellAnimalForm');
    const sellAnimalNameText = document.getElementById('sellAnimalNameText');
    const transferAnimalForm = document.getElementById('transferAnimalForm');
    const transferFromPanName = document.getElementById('transferFromPanName');
    const transferAnimalName = document.getElementById('transferAnimalName');
    const transferToPanId = document.getElementById('transferToPanId');
    const sellModalElement = document.getElementById('sellAnimalModal');
    const transferModalElement = document.getElementById('transferPanModal');
    const sellModal = sellModalElement ? new bootstrap.Modal(sellModalElement) : null;
    const transferModal = transferModalElement ? new bootstrap.Modal(transferModalElement) : null;

    document.querySelectorAll('.js-open-sell-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            const animalId = button.getAttribute('data-animal-id') || '';
            const animalName = button.getAttribute('data-animal-name') || '-';
            const tagNumber = button.getAttribute('data-tag-number') || '-';

            if (sellAnimalForm) {
                sellAnimalForm.action = "{{ url('/animal-lifecycle/active') }}/" + animalId + "/sell";
            }
            if (sellAnimalNameText) {
                sellAnimalNameText.textContent = `${animalName} (${tagNumber})`;
            }
            sellModal?.show();
        });
    });

    document.querySelectorAll('.js-open-transfer-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            const animalId = button.getAttribute('data-animal-id') || '';
            const animalName = button.getAttribute('data-animal-name') || '-';
            const tagNumber = button.getAttribute('data-tag-number') || '-';
            const farmerId = button.getAttribute('data-farmer-id') || '';
            const fromPanId = button.getAttribute('data-from-pan-id') || '';
            const fromPanName = button.getAttribute('data-from-pan-name') || '-';

            if (transferAnimalForm) {
                transferAnimalForm.action = "{{ url('/animal-lifecycle/active') }}/" + animalId + "/transfer";
            }

            if (transferFromPanName) {
                transferFromPanName.value = fromPanName;
            }
            if (transferAnimalName) {
                transferAnimalName.value = `${animalName} (${tagNumber})`;
            }
            if (transferToPanId) {
                transferToPanId.innerHTML = '';
                const destinations = ((window.activePanDestinationsMap && window.activePanDestinationsMap[farmerId]) || [])
                    .filter((pan) => String(pan.id) !== String(fromPanId));

                destinations.forEach((pan) => {
                    const option = document.createElement('option');
                    option.value = pan.id;
                    option.textContent = pan.name || `Pen #${pan.id}`;
                    transferToPanId.appendChild(option);
                });
            }

            if (!transferToPanId || transferToPanId.options.length === 0) {
                alert('Transfer is not possible: destination Pen missing.');
                return;
            }

            transferModal?.show();
        });
    });
});
</script>
@endif
@endpush
