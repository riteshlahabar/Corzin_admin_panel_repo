@extends('layouts.app')
@section('title', 'Add Vaccine')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="mt-4 mb-3">
        <h4 class="mb-0 text-dark">Add Vaccine</h4>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Create Vaccine</h6>
                    @perm('settings_vaccines.add')
                    <form method="POST" action="{{ route('settings.vaccines.store') }}" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Vaccine Name</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sort Order</label>
                            <input type="number" min="0" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', 0) }}">
                            @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                        <div class="col-12 d-grid mt-2">
                            <button type="submit" class="btn btn-success">Save Vaccine</button>
                        </div>
                    </form>
                    @else
                    <div class="alert alert-light border mb-0">You have view access only.</div>
                    @endperm
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('settings.vaccines.index') }}" class="row g-2 mb-3">
                        @if(request('per_page'))
                            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                        @endif
                        <div class="col-md-9">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search vaccine...">
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
                                    <th>Vaccine Name</th>
                                    <th>Sort Order</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vaccines as $vaccine)
                                    <tr>
                                        <td>{{ $vaccines->firstItem() + $loop->index }}</td>
                                        <td class="fw-semibold">{{ $vaccine->name }}</td>
                                        <td>{{ $vaccine->sort_order }}</td>
                                        <td>{{ $vaccine->description ?: '-' }}</td>
                                        <td>
                                            <span class="badge {{ $vaccine->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $vaccine->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            @perm('settings_vaccines.edit')
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editVaccine{{ $vaccine->id }}"
                                            >
                                                Edit
                                            </button>
                                            @endperm
                                            @perm('settings_vaccines.status')
                                            <form method="POST" action="{{ route('settings.vaccines.toggle', $vaccine) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-dark">
                                                    {{ $vaccine->is_active ? 'Disable' : 'Enable' }}
                                                </button>
                                            </form>
                                            @endperm

                                            @perm('settings_vaccines.edit')
                                            <div class="modal fade" id="editVaccine{{ $vaccine->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" action="{{ route('settings.vaccines.update', $vaccine) }}">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit Vaccine</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-2">
                                                                    <label class="form-label">Vaccine Name</label>
                                                                    <input type="text" name="name" class="form-control" value="{{ $vaccine->name }}" required>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label class="form-label">Sort Order</label>
                                                                    <input type="number" min="0" name="sort_order" class="form-control" value="{{ $vaccine->sort_order }}">
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label class="form-label">Description</label>
                                                                    <textarea name="description" rows="3" class="form-control">{{ $vaccine->description }}</textarea>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" value="1" id="active{{ $vaccine->id }}" name="is_active" {{ $vaccine->is_active ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="active{{ $vaccine->id }}">Active</label>
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
                                            @endperm
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No vaccine added yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @include('partials.table-pagination', ['paginator' => $vaccines])
            </div>
        </div>
    </div>
</div>
@endsection
