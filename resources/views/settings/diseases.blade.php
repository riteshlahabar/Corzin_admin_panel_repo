@extends('layouts.app')
@section('title', 'Add Disease')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="mt-4 mb-3">
        <h4 class="mb-0 text-dark">Add Disease</h4>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Create Disease</h6>
                    <form method="POST" action="{{ route('settings.diseases.store') }}" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Disease Name</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description (Optional)</label>
                            <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" min="0" class="form-control" value="{{ old('sort_order', 0) }}">
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                        <div class="col-12 d-grid mt-2">
                            <button type="submit" class="btn btn-success">Save Disease</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('settings.diseases.index') }}" class="row g-2 mb-3">
                        <div class="col-md-9">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search disease...">
                        </div>
                        <div class="col-md-3 d-grid">
                            <button type="submit" class="btn btn-success">Search</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Disease</th>
                                    <th>Description</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($diseases as $disease)
                                    <tr>
                                        <td>{{ $diseases->firstItem() + $loop->index }}</td>
                                        <td class="fw-semibold">{{ $disease->name }}</td>
                                        <td>{{ $disease->description ?: '-' }}</td>
                                        <td>{{ $disease->sort_order }}</td>
                                        <td>
                                            <span class="badge {{ $disease->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $disease->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editDisease{{ $disease->id }}"
                                            >
                                                Edit
                                            </button>
                                            <form method="POST" action="{{ route('settings.diseases.toggle', $disease) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-dark">
                                                    {{ $disease->is_active ? 'Disable' : 'Enable' }}
                                                </button>
                                            </form>

                                            <div class="modal fade" id="editDisease{{ $disease->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" action="{{ route('settings.diseases.update', $disease) }}">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit Disease</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-2">
                                                                    <label class="form-label">Disease Name</label>
                                                                    <input type="text" name="name" class="form-control" value="{{ $disease->name }}" required>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label class="form-label">Description</label>
                                                                    <textarea name="description" rows="3" class="form-control">{{ $disease->description }}</textarea>
                                                                </div>
                                                                <div class="row g-2">
                                                                    <div class="col-6">
                                                                        <label class="form-label">Sort Order</label>
                                                                        <input type="number" name="sort_order" min="0" class="form-control" value="{{ $disease->sort_order }}">
                                                                    </div>
                                                                    <div class="col-6 d-flex align-items-end">
                                                                        <div class="form-check">
                                                                            <input class="form-check-input" type="checkbox" value="1" id="active{{ $disease->id }}" name="is_active" {{ $disease->is_active ? 'checked' : '' }}>
                                                                            <label class="form-check-label" for="active{{ $disease->id }}">Active</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-success">Save</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No diseases added yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($diseases->hasPages())
                    <div class="card-footer bg-white">{{ $diseases->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
