@extends('layouts.app')
@section('title', 'Animal Buy/Sell')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-md-flex justify-content-md-between align-items-center">
                <h4 class="page-title">Animal Buy/Sell</h4>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Corzin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('shop.index') }}">Shop</a></li>
                    <li class="breadcrumb-item active">Selling List</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pb-0">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Selling List</h5>
                    <p class="text-muted mb-0 small">All animals listed for sale by farmers.</p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <input type="text" id="animalSaleSearch" class="form-control" placeholder="Search animal, tag, type, farmer..." style="width:280px;">
                    <div class="input-group" style="width:260px;">
                        <input type="date" id="saleStartDate" class="form-control">
                        <span class="input-group-text">to</span>
                        <input type="date" id="saleEndDate" class="form-control">
                    </div>
                    <button type="button" class="btn btn-light border" onclick="exportTableToPdf('animalBuySellTableExport', 'Animal Buy Sell')" title="Download PDF">
                        <i class="fa-solid fa-file-pdf text-danger"></i>
                    </button>
                    <button type="button" class="btn btn-light border" onclick="exportTableToExcel('animalBuySellTableExport', 'animal-buy-sell')" title="Download Excel">
                        <i class="fa-solid fa-file-excel text-success"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="animalBuySellTableExport">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Unique ID</th>
                            <th>Animal</th>
                            <th>Tag</th>
                            <th>Type</th>
                            <th>PAN</th>
                            <th>Gender</th>
                            <th>Birth Date</th>
                            <th>Age</th>
                            <th>Weight</th>
                            <th>Breed</th>
                            <th>Lactation</th>
                            <th>AI Date</th>
                            <th>Mother</th>
                            <th>Seller Name</th>
                            <th>Seller Mobile</th>
                            <th>Listed At</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sellingAnimals as $index => $animal)
                            @php
                                $sellerName = trim((optional($animal->farmer)->first_name ?? '').' '.(optional($animal->farmer)->last_name ?? '')) ?: '-';
                                $listedDate = optional($animal->listed_for_sale_at)->format('Y-m-d') ?: '';
                                $motherName = trim((optional($animal->motherAnimal)->animal_name ?? '').' '.(optional($animal->motherAnimal)->tag_number ? '('.optional($animal->motherAnimal)->tag_number.')' : '')) ?: '-';
                                $searchText = strtolower(trim(implode(' ', [
                                    $animal->unique_id,
                                    $animal->animal_name,
                                    $animal->tag_number,
                                    optional($animal->animalType)->name,
                                    optional($animal->pan)->name,
                                    $animal->gender,
                                    $animal->weight,
                                    $animal->breed_name,
                                    $sellerName,
                                    optional($animal->farmer)->mobile,
                                ])));
                            @endphp
                            <tr class="animal-sale-row" data-search="{{ $searchText }}" data-date="{{ $listedDate }}">
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    @if($animal->image_url)
                                        <img src="{{ $animal->image_url }}" alt="Animal" class="rounded" style="height:44px;width:56px;object-fit:cover;">
                                    @else
                                        <span class="d-inline-flex align-items-center justify-content-center rounded bg-success-subtle text-success" style="height:44px;width:56px;">
                                            <i class="iconoir-wolf fs-5"></i>
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $animal->unique_id ?: '-' }}</td>
                                <td class="fw-semibold">{{ $animal->animal_name ?: '-' }}</td>
                                <td>{{ $animal->tag_number ?: '-' }}</td>
                                <td>{{ optional($animal->animalType)->name ?: '-' }}</td>
                                <td>{{ optional($animal->pan)->name ?: '-' }}</td>
                                <td>{{ $animal->gender ?: '-' }}</td>
                                <td>{{ $animal->birth_date ? \Carbon\Carbon::parse($animal->birth_date)->format('d-m-Y') : '-' }}</td>
                                <td>{{ $animal->calculated_age ?? '-' }}</td>
                                <td>{{ $animal->weight ? $animal->weight.' kg' : '-' }}</td>
                                <td>{{ $animal->breed_name ?: '-' }}</td>
                                <td>{{ $animal->lactation_number ?? '-' }}</td>
                                <td>{{ optional($animal->ai_date)->format('d-m-Y') ?: '-' }}</td>
                                <td>{{ $motherName }}</td>
                                <td>{{ $sellerName }}</td>
                                <td>{{ optional($animal->farmer)->mobile ?: '-' }}</td>
                                <td>{{ optional($animal->listed_for_sale_at)->format('d-m-Y h:i A') ?: '-' }}</td>
                                <td><span class="badge bg-warning-subtle text-warning">For Sale</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="19" class="text-center text-muted py-4">No animals are currently listed for selling.</td>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('animalSaleSearch');
    const start = document.getElementById('saleStartDate');
    const end = document.getElementById('saleEndDate');
    const rows = Array.from(document.querySelectorAll('.animal-sale-row'));

    function applyFilters() {
        const term = (search?.value || '').trim().toLowerCase();
        const startDate = start?.value || '';
        const endDate = end?.value || '';

        rows.forEach((row) => {
            const text = row.dataset.search || '';
            const date = row.dataset.date || '';
            const matchesSearch = !term || text.includes(term);
            const matchesStart = !startDate || (date && date >= startDate);
            const matchesEnd = !endDate || (date && date <= endDate);
            row.style.display = matchesSearch && matchesStart && matchesEnd ? '' : 'none';
        });
    }

    [search, start, end].forEach((element) => {
        if (!element) return;
        element.addEventListener('input', applyFilters);
        element.addEventListener('change', applyFilters);
    });
});
</script>
@endpush
