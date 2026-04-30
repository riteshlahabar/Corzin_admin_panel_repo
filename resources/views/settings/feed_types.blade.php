@extends('layouts.app')
@section('title', 'Add Feed Type')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="mt-4 mb-3">
        <h4 class="mb-0 text-dark">Add Feed Type</h4>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Create Feed Type</h6>
                    <form method="POST" action="{{ route('settings.feed-types.store') }}" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Feed Type Name</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Default Unit</label>
                            <input type="text" name="default_unit" class="form-control @error('default_unit') is-invalid @enderror" value="{{ old('default_unit', 'Kg') }}" placeholder="Ex: Kg, Gram, Bundle" required>
                            @error('default_unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Subtypes (comma or new line)</label>
                            <textarea name="subtypes_text" rows="4" class="form-control @error('subtypes_text') is-invalid @enderror" required>{{ old('subtypes_text') }}</textarea>
                            @error('subtypes_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                        <div class="col-12 d-grid mt-2">
                            <button type="submit" class="btn btn-success">Save Feed Type</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('settings.feed-types.index') }}" class="row g-2 mb-3">
                        <div class="col-md-9">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search feed type...">
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
                                    <th>Feed Type</th>
                                    <th>Unit</th>
                                    <th>Subtypes</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($feedTypes as $feedType)
                                    <tr>
                                        <td>{{ $feedTypes->firstItem() + $loop->index }}</td>
                                        <td class="fw-semibold">{{ $feedType->name }}</td>
                                        <td>{{ $feedType->default_unit }}</td>
                                        <td>
                                            @if($feedType->subtypes->isEmpty())
                                                <span class="text-muted">-</span>
                                            @else
                                                <span class="text-muted">{{ $feedType->subtypes->pluck('name')->implode(', ') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $feedType->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $feedType->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editFeedType{{ $feedType->id }}"
                                            >
                                                Edit
                                            </button>
                                            <form method="POST" action="{{ route('settings.feed-types.toggle', $feedType) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-dark">
                                                    {{ $feedType->is_active ? 'Disable' : 'Enable' }}
                                                </button>
                                            </form>

                                            <div class="modal fade" id="editFeedType{{ $feedType->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" action="{{ route('settings.feed-types.update', $feedType) }}">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit Feed Type</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-2">
                                                                    <label class="form-label">Feed Type Name</label>
                                                                    <input type="text" name="name" class="form-control" value="{{ $feedType->name }}" required>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label class="form-label">Default Unit</label>
                                                                    <input type="text" name="default_unit" class="form-control" value="{{ $feedType->default_unit }}" placeholder="Ex: Kg, Gram, Bundle" required>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label class="form-label">Subtypes (comma or new line)</label>
                                                                    <textarea name="subtypes_text" rows="4" class="form-control" required>{{ $feedType->subtypes->pluck('name')->implode("\n") }}</textarea>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" value="1" id="active{{ $feedType->id }}" name="is_active" {{ $feedType->is_active ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="active{{ $feedType->id }}">Active</label>
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
                                        <td colspan="6" class="text-center text-muted py-4">No feed type added yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($feedTypes->hasPages())
                    <div class="card-footer bg-white">{{ $feedTypes->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
