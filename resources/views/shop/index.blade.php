@extends('layouts.app')

@php
    $activeTab = $activeTab ?? request('tab', 'add-product');
@endphp

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(!empty($shopNotifications) && $shopNotifications->count() > 0)
        <div class="alert alert-info">
            <div class="fw-semibold mb-1">Latest Shop Notifications</div>
            @foreach($shopNotifications as $notification)
                <div class="small">• {{ $notification->message }} <span class="text-muted">({{ optional($notification->created_at)->format('d-m-Y h:i A') }})</span></div>
            @endforeach
        </div>
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
                <li class="nav-item"><a class="nav-link {{ $activeTab === 'add-product' ? 'active' : '' }}" href="{{ route('shop.index', ['tab' => 'add-product']) }}">Product List</a></li>
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
                                <th>Medicine Setup</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $key => $product)
                                <tr class="shop-row" data-search="{{ strtolower($product->category.' '.$product->name.' '.($product->company_name ?? '').' '.($product->hsn_code ?? '').' '.($product->subtitle ?? '').' '.($product->description ?? '').' '.($product->medicine_aliases ?? '')) }}">
                                    <td>{{ $key + 1 }}</td>
                                    <td>@if(!empty($product->image))<img src="{{ asset($product->image) }}" alt="{{ $product->name }}" style="height:46px;width:46px;object-fit:cover;border-radius:10px;">@else <span class="text-muted">-</span>@endif</td>
                                    <td><span class="badge bg-light text-dark text-capitalize">{{ $product->category }}</span></td>
                                    <td>
                                        <div class="fw-semibold">{{ $product->name }}</div>
                                        @if(!empty($product->company_name))
                                            <div class="small text-muted">Company: {{ $product->company_name }}</div>
                                        @endif
                                        @if(!empty($product->hsn_code))
                                            <div class="small text-muted">HSN: {{ $product->hsn_code }}</div>
                                        @endif
                                    </td>
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
                                    <td>
                                        @if(strtolower((string) $product->category) === 'medicine')
                                            <div class="small">Pack: {{ $product->pack_size ?: '-' }}</div>
                                            <div class="small">Partial: {{ !empty($product->allow_partial_units) ? 'Yes' : 'No' }}</div>
                                            @if(!empty($product->medicine_aliases))
                                                <div class="small text-muted text-truncate" style="max-width:180px;" title="{{ $product->medicine_aliases }}">
                                                    Aliases: {{ \Illuminate\Support\Str::limit($product->medicine_aliases, 35) }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td><span class="badge {{ $product->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted">No products found</td></tr>
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
            <form method="POST" action="{{ route('shop.store') }}" enctype="multipart/form-data" id="shopProductForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Shop Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <div class="d-flex align-items-center gap-2">
                                <select name="category" id="shopCategory" class="form-select" required>
                                    <option value="">Select category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category }}">{{ ucfirst($category) }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary btn-sm px-2" data-bs-toggle="modal" data-bs-target="#addCategoryModal">+</button>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="editCategoryTrigger">Edit</button>
                            </div>
                        </div>
                        <div class="col-md-6"><label class="form-label">Product Name</label><input type="text" name="name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Company Name</label><input type="text" name="company_name" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">HSN Code</label><input type="text" name="hsn_code" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Subtitle</label><input type="text" name="subtitle" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Price</label><input type="number" step="0.01" min="0" name="price" class="form-control" required></div>
                        <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-control"></textarea></div>
                        <div class="col-12"><label class="form-label">Features (one per line)</label><textarea name="features" rows="3" class="form-control" placeholder="High protein&#10;Fast delivery&#10;Best for milking cows"></textarea></div>
                        <div class="col-md-3"><label class="form-label">Packing Size</label><input type="number" min="1" name="pack_size" class="form-control" placeholder="80 or 50"></div>
                        <div class="col-md-3">
                            <label class="form-label">Unit</label>
                            <div class="d-flex align-items-center gap-2">
                                <select name="unit" id="shopUnit" class="form-select">
                                    <option value="">Select unit</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit }}">{{ ucfirst($unit) }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary btn-sm px-2" data-bs-toggle="modal" data-bs-target="#addUnitModal">+</button>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="editUnitTrigger">Edit</button>
                            </div>
                            <small class="text-muted">Example: packing size `50` + unit `kg` = `50 kg`</small>
                        </div>
                        <div class="col-12" id="medicineFields">
                            <div class="row g-3">
                                <div class="col-12"><label class="form-label">Medicine Aliases (for prescription match)</label><textarea name="medicine_aliases" rows="2" class="form-control" placeholder="Paracetamol&#10;PCM&#10;Acetaminophen"></textarea><small class="text-muted">Optional. Use line break or comma separated values.</small></div>
                                <div class="col-md-6 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="allow_partial_units" value="1" id="allowPartialUnits"><label class="form-check-label" for="allowPartialUnits">Allow partial quantity</label></div></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded-3 p-3 bg-light-subtle">
                                <div class="fw-semibold mb-3">Product Images</div>
                                <div class="row g-3">
                                    <div class="col-12"><label class="form-label">Main Image</label><input type="file" name="image" class="form-control" accept="image/*"></div>
                                    <div class="col-12"><label class="form-label">Gallery Images</label><input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple></div>
                                </div>
                            </div>
                        </div>
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

<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('shop.categories.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Category Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="editCategoryForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Category Name</label>
                    <input type="text" name="name" id="editCategoryName" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addUnitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('shop.units.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Unit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Unit Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editUnitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="editUnitForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Unit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Unit Name</label>
                    <input type="text" name="name" id="editUnitName" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
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

const shopCategory = document.getElementById('shopCategory');
const medicineFields = document.getElementById('medicineFields');
const editCategoryTrigger = document.getElementById('editCategoryTrigger');
const editUnitTrigger = document.getElementById('editUnitTrigger');
const shopProductForm = document.getElementById('shopProductForm');
const shopDraftKey = 'shopProductDraft';

function toggleMedicineFields() {
    const selectedCategory = (shopCategory?.value || '').toLowerCase();
    if (!medicineFields) {
        return;
    }

    const isMedicine = selectedCategory === 'medicine';
    medicineFields.style.display = isMedicine ? '' : 'none';
    medicineFields.querySelectorAll('textarea, input[type="checkbox"]').forEach((field) => {
        if (field.name === 'allow_partial_units') {
            field.checked = isMedicine ? field.checked : false;
            return;
        }
    });
}

function selectedOptionValue(selectId) {
    const select = document.getElementById(selectId);
    return select?.value || '';
}

function openEditLookupModal(selectId, formId, inputId, baseUrl, modalId) {
    const value = selectedOptionValue(selectId);
    if (!value) {
        return;
    }

    const select = document.getElementById(selectId);
    const option = select.options[select.selectedIndex];
    const label = option?.text?.trim() || value;
    document.getElementById(inputId).value = value;
    document.getElementById(formId).action = `${baseUrl}/${encodeURIComponent(value)}`;

    const modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();
}

function captureShopProductDraft() {
    if (!shopProductForm) {
        return;
    }

    const payload = {};
    shopProductForm.querySelectorAll('input[name], textarea[name], select[name]').forEach((field) => {
        if (field.type === 'file') {
            return;
        }

        if (field.type === 'checkbox') {
            payload[field.name] = field.checked;
            return;
        }

        payload[field.name] = field.value;
    });

    localStorage.setItem(shopDraftKey, JSON.stringify(payload));
}

function restoreShopProductDraft() {
    if (!shopProductForm) {
        return;
    }

    const raw = localStorage.getItem(shopDraftKey);
    if (!raw) {
        return;
    }

    try {
        const payload = JSON.parse(raw);
        Object.entries(payload).forEach(([name, value]) => {
            const field = shopProductForm.querySelector(`[name="${name}"]`);
            if (!field || field.type === 'file') {
                return;
            }

            if (field.type === 'checkbox') {
                field.checked = Boolean(value);
                return;
            }

            field.value = value ?? '';
        });
    } catch (error) {
        localStorage.removeItem(shopDraftKey);
    }

    toggleMedicineFields();
}

function clearShopProductDraft() {
    localStorage.removeItem(shopDraftKey);
}

shopCategory?.addEventListener('change', toggleMedicineFields);
shopProductForm?.querySelectorAll('input[name], textarea[name], select[name]').forEach((field) => {
    if (field.type === 'file') {
        return;
    }

    field.addEventListener('input', captureShopProductDraft);
    field.addEventListener('change', captureShopProductDraft);
});

const shouldClearDraft = @json((bool) session('shop_product_saved'));
if (shouldClearDraft) {
    clearShopProductDraft();
} else {
    restoreShopProductDraft();
}

const selectedCategoryFromQuery = @json(request('selected_category'));
if (selectedCategoryFromQuery && shopCategory) {
    shopCategory.value = selectedCategoryFromQuery;
}

const shopUnit = document.getElementById('shopUnit');
const selectedUnitFromQuery = @json(request('selected_unit'));
if (selectedUnitFromQuery && shopUnit) {
    shopUnit.value = selectedUnitFromQuery;
}

toggleMedicineFields();
captureShopProductDraft();

editCategoryTrigger?.addEventListener('click', function () {
    openEditLookupModal(
        'shopCategory',
        'editCategoryForm',
        'editCategoryName',
        '{{ url('/shop/categories') }}',
        'editCategoryModal'
    );
});

editUnitTrigger?.addEventListener('click', function () {
    openEditLookupModal(
        'shopUnit',
        'editUnitForm',
        'editUnitName',
        '{{ url('/shop/units') }}',
        'editUnitModal'
    );
});

@if(request('modal') === 'add-product')
const addShopModal = document.getElementById('addShopModal');
if (addShopModal) {
    const modal = new bootstrap.Modal(addShopModal);
    modal.show();
}
@endif
</script>
@endpush
