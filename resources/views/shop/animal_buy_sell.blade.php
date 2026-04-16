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
        <div class="card-header bg-transparent border-0">
            <h5 class="mb-0">Selling List</h5>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Animal</th>
                            <th>Tag</th>
                            <th>Type</th>
                            <th>Seller Name</th>
                            <th>Seller Mobile</th>
                            <th>Listed At</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sellingAnimals as $index => $animal)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $animal->animal_name ?: '-' }}</td>
                                <td>{{ $animal->tag_number ?: '-' }}</td>
                                <td>{{ optional($animal->animalType)->name ?: '-' }}</td>
                                <td>{{ trim((optional($animal->farmer)->first_name ?? '').' '.(optional($animal->farmer)->last_name ?? '')) ?: '-' }}</td>
                                <td>{{ optional($animal->farmer)->mobile ?: '-' }}</td>
                                <td>{{ optional($animal->listed_for_sale_at)->format('d-m-Y h:i A') ?: '-' }}</td>
                                <td><span class="badge bg-warning-subtle text-warning">For Sale</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No animals are currently listed for selling.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

