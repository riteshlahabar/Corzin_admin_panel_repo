@extends('layouts.app')
@section('title', 'Feed Sub Type')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="row mt-4 mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="mb-0 text-dark">Feed Sub Type</h4>
            @perm('farmer_feed_subtypes.add')
            <a href="{{ route('farmer.feed-subtypes.create') }}" class="btn btn-success">
                <i class="fa-solid fa-plus me-1"></i> Add Feed Sub Type
            </a>
            @endperm
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('farmer.feed-subtypes.index') }}" class="row g-2 mb-3">
                @if(request('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif
                <div class="col-lg-4 col-md-6">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search farmer, mobile, feed type or subtype...">
                </div>
                <div class="col-lg-3 col-md-6">
                    <select name="farmer_id" class="form-select">
                        <option value="">All Farmers</option>
                        @foreach($farmers as $farmer)
                            <option value="{{ $farmer->id }}" {{ (string) request('farmer_id') === (string) $farmer->id ? 'selected' : '' }}>
                                {{ trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) ?: 'Farmer #'.$farmer->id }} - {{ $farmer->mobile }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <select name="feed_type_id" class="form-select">
                        <option value="">All Feed Types</option>
                        @foreach($feedTypes as $feedType)
                            <option value="{{ $feedType->id }}" {{ (string) request('feed_type_id') === (string) $feedType->id ? 'selected' : '' }}>
                                {{ $feedType->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-1 col-md-4">
                    <select name="status" class="form-select">
                        <option value="">Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-lg-1 col-md-2 d-grid">
                    <button type="submit" class="btn btn-success">Search</button>
                </div>
                <div class="col-lg-1 col-md-2 d-grid">
                    <a href="{{ route('farmer.feed-subtypes.index') }}" class="btn btn-light border">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Farmer</th>
                            <th>Mobile</th>
                            <th>Feed Type</th>
                            <th>Feed Sub Type</th>
                            <th>Status</th>
                            <th>Updated At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subtypes as $subtype)
                            <tr>
                                <td>{{ $subtypes->firstItem() + $loop->index }}</td>
                                <td>{{ trim(($subtype->farmer->first_name ?? '').' '.($subtype->farmer->last_name ?? '')) ?: 'Farmer #'.$subtype->farmer_id }}</td>
                                <td>{{ $subtype->farmer->mobile ?? '-' }}</td>
                                <td>{{ $subtype->feedType->name ?? '-' }}</td>
                                <td class="fw-semibold">{{ $subtype->name }}</td>
                                <td>
                                    <span class="badge {{ $subtype->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $subtype->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ optional($subtype->updated_at)->format('d-m-Y h:i A') ?: '-' }}</td>
                                <td class="text-nowrap">
                                    @perm('farmer_feed_subtypes.edit')
                                    <a href="{{ route('farmer.feed-subtypes.edit', $subtype) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @endperm
                                    @perm('farmer_feed_subtypes.status')
                                    <form method="POST" action="{{ route('farmer.feed-subtypes.toggle', $subtype) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-dark">
                                            {{ $subtype->is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                    @endperm
                                    @perm('farmer_feed_subtypes.delete')
                                    <form method="POST" action="{{ route('farmer.feed-subtypes.destroy', $subtype) }}" class="d-inline" onsubmit="return confirm('Delete this feed subtype?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                    @endperm
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No farmer-wise feed subtype found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @include('partials.table-pagination', ['paginator' => $subtypes])
    </div>
</div>
@endsection
