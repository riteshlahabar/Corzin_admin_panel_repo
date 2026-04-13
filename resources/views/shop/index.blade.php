@extends('layouts.app')

@php
    $activeTab = $activeTab ?? request('tab', 'add-product');
@endphp

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mt-2 mb-3">
        <div class="col-md-3">
            <div class="card bg-primary-subtle border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">Products</p>
                    <h4 class="mb-0">{{ $summary['total'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning-subtle border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">New Orders</p>
                    <h4 class="mb-0">{{ $summary['new_orders'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info-subtle border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">In Progress</p>
                    <h4 class="mb-0">{{ $summary['in_progress_orders'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success-subtle border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">Completed</p>
                    <h4 class="mb-0">{{ $summary['completed_orders'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <ul class="nav nav-pills mb-3 flex-wrap gap-2">
                <li class="nav-item"><a class="nav-link {{ $activeTab === 'add-product' ? 'active' : '' }}" href="{{ route('shop.index', ['tab' => 'add-product']) }}">Add Product</a></li>
                <li class="nav-item"><a class="nav-link {{ $activeTab === 'new-order' ? 'active' : '' }}" href="{{ route('shop.index', ['tab' => 'new-order']) }}">New Order</a></li>
                <li class="nav-item"><a class="nav-link {{ $activeTab === 'in-progress' ? 'active' : '' }}" href="{{ route('shop.index', ['tab' => 'in-progress']) }}">Order In Progress</a></li>
                <li class="nav-item"><a class="nav-link {{ $activeTab === 'completed' ? 'active' : '' }}" href="{{ route('shop.index', ['tab' => 'completed']) }}">Order Completed</a></li>
                <li class="nav-item"><a class="nav-link {{ $activeTab === 'payment' ? 'active' : '' }}" href="{{ route('shop.index', ['tab' => 'payment']) }}">Order Payment</a></li>
            </ul>

            @if($activeTab === 'add-product')
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h5 class="mb-0">Product Catalog</h5>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <input type="text" id="shopSearch" class="form-control" placeholder="Search product..." style="width:220px;">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addShopModal">
                            <i class="fa-solid fa-plus me-1"></i> Add Product
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Image</th>
                                <th>Category</th>
                                <th>Name</th>
                                <th style="min-width: 260px;">Description</th>
                                <th>Price</th>
                                <th>Unit</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $key => $product)
                                <tr class="shop-row" data-search="{{ strtolower($product->category.' '.$product->name.' '.($product->subtitle ?? '').' '.($product->description ?? '')) }}">
                                    <td>{{ $key + 1 }}</td>
                                    <td>@if(!empty($product->image))<img src="{{ asset($product->image) }}" alt="{{ $product->name }}" style="height:46px;width:46px;object-fit:cover;border-radius:10px;">@else <span class="text-muted">-</span>@endif</td>
                                    <td><span class="badge bg-light text-dark text-capitalize">{{ $product->category }}</span></td>
                                    <td>{{ $product->name }}</td>
                                    <td>
                                        @php
                                            $fullDescription = trim((string) ($product->description ?? ''));
                                            $shortDescription = \Illuminate\Support\Str::limit($fullDescription, 55, '');
                                        @endphp
                                        @if($fullDescription !== '')
                                            <span>{{ $shortDescription }}</span>
                                            @if(\Illuminate\Support\Str::length($fullDescription) > 55)
                                                <span class="text-primary ms-1" style="cursor: help;" title="{{ $fullDescription }}">more...</span>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>Rs {{ number_format((float) $product->price, 2) }}</td>
                                    <td>{{ $product->unit ?: '-' }}</td>
                                    <td><span class="badge {{ $product->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted">No products found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif($activeTab === 'new-order')
                @include('shop.partials.orders_table', ['orders' => $newOrders, 'tab' => 'new-order', 'title' => 'New Orders'])
            @elseif($activeTab === 'in-progress')
                @include('shop.partials.orders_table', ['orders' => $inProgressOrders, 'tab' => 'in-progress', 'title' => 'Orders In Progress'])
            @elseif($activeTab === 'completed')
                @include('shop.partials.orders_table', ['orders' => $completedOrders, 'tab' => 'completed', 'title' => 'Completed Orders'])
            @elseif($activeTab === 'payment')
                @include('shop.partials.orders_table', ['orders' => $paymentOrders, 'tab' => 'payment', 'title' => 'Order Payment'])
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="addShopModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('shop.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Shop Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Category</label><input type="text" name="category" class="form-control" placeholder="feed / supplements / medicine / equipment" required></div>
                        <div class="col-md-6"><label class="form-label">Product Name</label><input type="text" name="name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Subtitle</label><input type="text" name="subtitle" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Price</label><input type="number" step="0.01" min="0" name="price" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label">Unit</label><input type="text" name="unit" class="form-control" placeholder="bag / bottle / kg"></div>
                        <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-control"></textarea></div>
                        <div class="col-12"><label class="form-label">Features (one per line)</label><textarea name="features" rows="3" class="form-control" placeholder="High protein&#10;Fast delivery&#10;Best for milking cows"></textarea></div>
                        <div class="col-md-6"><label class="form-label">Main Image</label><input type="file" name="image" class="form-control" accept="image/*"></div>
                        <div class="col-md-6"><label class="form-label">Gallery Images</label><input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple></div>
                        <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="shopActive" checked><label class="form-check-label" for="shopActive">Active</label></div></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('shopSearch')?.addEventListener('input', function () {
    const value = this.value.toLowerCase().trim();
    document.querySelectorAll('.shop-row').forEach((row) => {
        const haystack = row.dataset.search || '';
        row.style.display = haystack.includes(value) ? '' : 'none';
    });
});
</script>
@endpush
