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
                    <select id="animalSaleSearchField" class="form-select" style="width:220px;">
                        <option value="all">All Columns</option>
                        <option value="unique-id">Unique ID</option>
                        <option value="animal">Animal</option>
                        <option value="tag">Tag</option>
                        <option value="type">Type</option>
                        <option value="pan">Pen</option>
                        <option value="gender">Gender</option>
                        <option value="birth-date">Birth Date</option>
                        <option value="age">Age</option>
                        <option value="weight">Weight</option>
                        <option value="breed">Breed</option>
                        <option value="lactation">Lactation</option>
                        <option value="ai-date">AI Date</option>
                        <option value="farmer-name">Farmer Name</option>
                        <option value="farmer-mobile">Farmer Mobile</option>
                        <option value="listed-at">Listed At</option>
                        <option value="status">Status</option>
                    </select>
                    <input type="text" id="animalSaleSearch" class="form-control" placeholder="Search selected field..." style="width:220px;">
                    <div class="input-group" style="width:260px;">
                        <input type="date" id="saleStartDate" class="form-control">
                        <span class="input-group-text">to</span>
                        <input type="date" id="saleEndDate" class="form-control">
                    </div>
                    @perm('shop_animal_buy_sell.export')
                    <button type="button" class="btn btn-light border" onclick="exportTableToPdf('animalBuySellTableExport', 'Animal Buy Sell')" title="Download PDF">
                        <i class="fa-solid fa-file-pdf text-danger"></i>
                    </button>
                    <button type="button" class="btn btn-light border" onclick="exportTableToExcel('animalBuySellTableExport', 'animal-buy-sell')" title="Download Excel">
                        <i class="fa-solid fa-file-excel text-success"></i>
                    </button>
                    @endperm
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
                            <th>Pen</th>
                            <th>Gender</th>
                            <th>Birth Date</th>
                            <th>Age</th>
                            <th>Weight</th>
                            <th>Breed</th>
                            <th>Lactation</th>
                            <th>AI Date</th>
                            <th>Farmer Name</th>
                            <th>Farmer Mobile</th>
                            <th>Listed At</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sellingAnimals as $index => $animal)
                            @php
                                $sellerName = trim((optional($animal->farmer)->first_name ?? '').' '.(optional($animal->farmer)->last_name ?? '')) ?: '-';
                                $listedDate = optional($animal->listed_for_sale_at)->format('Y-m-d') ?: '';
                                $birthDate = $animal->birth_date ? \Carbon\Carbon::parse($animal->birth_date)->format('d-m-Y') : '-';
                                $age = $animal->calculated_age ?? '-';
                                $weight = $animal->weight ? $animal->weight.' kg' : '-';
                                $type = optional($animal->animalType)->name ?: '-';
                                $panName = optional($animal->pan)->name ?: '-';
                                $gender = $animal->gender ?: '-';
                                $breed = $animal->breed_name ?: '-';
                                $lactation = $animal->lactation_number ?? '-';
                                $aiDate = optional($animal->ai_date)->format('d-m-Y') ?: '-';
                                $farmerMobile = optional($animal->farmer)->mobile ?: '-';
                                $listedAt = optional($animal->listed_for_sale_at)->format('d-m-Y h:i A') ?: '-';
                                $status = 'For Sale';
                                $searchText = strtolower(trim(implode(' ', [
                                    $animal->unique_id,
                                    $animal->animal_name,
                                    $animal->tag_number,
                                    $type,
                                    $panName,
                                    $gender,
                                    $birthDate,
                                    $age,
                                    $weight,
                                    $breed,
                                    $lactation,
                                    $aiDate,
                                    $sellerName,
                                    $farmerMobile,
                                    $listedAt,
                                    $status,
                                ])));
                            @endphp
                            <tr class="animal-sale-row"
                                data-all="{{ $searchText }}"
                                data-unique-id="{{ strtolower((string) ($animal->unique_id ?: '-')) }}"
                                data-animal="{{ strtolower((string) ($animal->animal_name ?: '-')) }}"
                                data-tag="{{ strtolower((string) ($animal->tag_number ?: '-')) }}"
                                data-type="{{ strtolower($type) }}"
                                data-pan="{{ strtolower($panName) }}"
                                data-gender="{{ strtolower($gender) }}"
                                data-birth-date="{{ strtolower($birthDate) }}"
                                data-age="{{ strtolower((string) $age) }}"
                                data-weight="{{ strtolower($weight) }}"
                                data-breed="{{ strtolower($breed) }}"
                                data-lactation="{{ strtolower((string) $lactation) }}"
                                data-ai-date="{{ strtolower($aiDate) }}"
                                data-farmer-name="{{ strtolower($sellerName) }}"
                                data-farmer-mobile="{{ strtolower($farmerMobile) }}"
                                data-listed-at="{{ strtolower($listedAt) }}"
                                data-status="{{ strtolower($status) }}"
                                data-date="{{ $listedDate }}">
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
                                <td>{{ $type }}</td>
                                <td>{{ $panName }}</td>
                                <td>{{ $gender }}</td>
                                <td>{{ $birthDate }}</td>
                                <td>{{ $age }}</td>
                                <td>{{ $weight }}</td>
                                <td>{{ $breed }}</td>
                                <td>{{ $lactation }}</td>
                                <td>{{ $aiDate }}</td>
                                <td>{{ $sellerName }}</td>
                                <td>{{ $farmerMobile }}</td>
                                <td>{{ $listedAt }}</td>
                                <td><span class="badge bg-warning-subtle text-warning">{{ $status }}</span></td>
                                <td>
                                    <form method="POST" action="{{ route('shop.animal_buy_sell.cancel', $animal) }}" onsubmit="return confirm('Cancel selling for this animal?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Cancel Selling</button>
                                    </form>
                                </td>
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
    const searchField = document.getElementById('animalSaleSearchField');
    const start = document.getElementById('saleStartDate');
    const end = document.getElementById('saleEndDate');
    const rows = Array.from(document.querySelectorAll('.animal-sale-row'));

    function datasetValue(row, field) {
        const key = field.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
        return row.dataset[key] || '';
    }

    function applyFilters() {
        const term = (search?.value || '').trim().toLowerCase();
        const selectedField = searchField?.value || 'all';
        const startDate = start?.value || '';
        const endDate = end?.value || '';

        rows.forEach((row) => {
            const text = selectedField === 'all'
                ? (row.dataset.all || '')
                : datasetValue(row, selectedField);
            const date = row.dataset.date || '';
            const matchesSearch = !term || text.includes(term);
            const matchesStart = !startDate || (date && date >= startDate);
            const matchesEnd = !endDate || (date && date <= endDate);
            row.style.display = matchesSearch && matchesStart && matchesEnd ? '' : 'none';
        });
    }

    [search, searchField, start, end].forEach((element) => {
        if (!element) return;
        element.addEventListener('input', applyFilters);
        element.addEventListener('change', applyFilters);
    });
});
</script>
@endpush
