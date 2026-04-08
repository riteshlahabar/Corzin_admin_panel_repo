@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card bg-primary-subtle border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">Total Products</p>
                    <h3 class="mb-0">{{ $summary['total'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning-subtle border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">Categories</p>
                    <h3 class="mb-0">{{ $summary['categories'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success-subtle border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">Active Products</p>
                    <h3 class="mb-0">{{ $summary['active'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">Shop Products</h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <input type="text" id="shopSearch" class="form-control" placeholder="Search product..." style="width:220px;">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addShopModal">
                    <i class="fa-solid fa-plus me-1"></i> Add Product
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Category</th>
                            <th>Name</th>
                            <th>Subtitle</th>
                            <th>Price</th>
                            <th>Unit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $key => $product)
                            <tr class="shop-row" data-search="{{ strtolower($product->category.' '.$product->name.' '.($product->subtitle ?? '').' '.($product->description ?? '')) }}">
                                <td>{{ $key + 1 }}</td>
                                <td><span class="badge bg-light text-dark text-capitalize">{{ $product->category }}</span></td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->subtitle ?: '-' }}</td>
                                <td>Rs {{ number_format((float) $product->price, 2) }}</td>
                                <td>{{ $product->unit ?: '-' }}</td>
                                <td>
                                    <span class="badge {{ $product->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ $product->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No products found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addShopModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('shop.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Shop Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" placeholder="feed / supplements / medicine / equipment" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Subtitle</label>
                            <input type="text" name="subtitle" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Price</label>
                            <input type="number" step="0.01" min="0" name="price" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit" class="form-control" placeholder="bag / bottle / kg">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="shopActive" checked>
                                <label class="form-check-label" for="shopActive">Active</label>
                            </div>
                        </div>
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
