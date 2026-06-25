@extends('layouts.app')
@section('title', 'Role Management')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
    @endif

    <div class="mt-4 mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="mb-0 text-dark">Role</h4>
            <p class="text-muted mb-0">Create admin roles and decide exactly which screens and actions they can use.</p>
        </div>
        @perm('settings_roles.add')
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createRoleModal">
            <i class="fa-solid fa-plus me-1"></i> Create Role
        </button>
        @endperm
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('settings.roles.index') }}" class="row g-2 mb-3">
                @if(request('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif
                <div class="col-md-9">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search role...">
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
                            <th>Role</th>
                            <th>Slug</th>
                            <th>Permissions</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                            <tr>
                                <td>{{ $roles->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $role->name }}</div>
                                    <small class="text-muted">{{ $role->description ?: '-' }}</small>
                                </td>
                                <td><code>{{ $role->slug }}</code></td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary">
                                        {{ \App\Services\AdminAccess::permissionCount((array) ($role->permissions ?? [])) }} permissions
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $role->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $role->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    @if($role->is_system)
                                        <span class="badge bg-warning text-dark">System</span>
                                    @endif
                                </td>
                                <td>
                                    @perm('settings_roles.edit')
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editRole{{ $role->id }}">Edit</button>
                                    @endperm
                                    @perm('settings_roles.status')
                                    <form method="POST" action="{{ route('settings.roles.toggle', $role) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-dark">
                                            {{ $role->is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                    @endperm
                                </td>
                            </tr>

                            @perm('settings_roles.edit')
                            <div class="modal fade" id="editRole{{ $role->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('settings.roles.update', $role) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Role</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Role Name</label>
                                                        <input type="text" name="name" class="form-control" value="{{ $role->name }}" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Slug</label>
                                                        <input type="text" name="slug" class="form-control" value="{{ $role->slug }}">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Description</label>
                                                        <textarea name="description" rows="2" class="form-control">{{ $role->description }}</textarea>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" value="1" id="activeRole{{ $role->id }}" name="is_active" {{ $role->is_active ? 'checked' : '' }} {{ $role->is_system && $role->slug === 'admin' ? 'disabled' : '' }}>
                                                            <label class="form-check-label" for="activeRole{{ $role->id }}">Active Role</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="border rounded-3 p-3 bg-light-subtle permission-matrix-wrap">
                                                            @foreach($permissionGroups as $group)
                                                                <div class="border rounded-3 bg-white p-2 mb-2">
                                                                    <div class="fw-semibold text-dark mb-2">{{ $group['label'] }}</div>
                                                                    @foreach($group['resources'] as $resourceKey => $resource)
                                                                        <div class="permission-row py-2 border-top {{ $loop->first ? 'border-top-0' : '' }}">
                                                                            <div class="small fw-semibold text-dark mb-1">{{ $resource['label'] }}</div>
                                                                            <div class="d-flex flex-wrap gap-2">
                                                                                @foreach($resource['actions'] as $actionKey => $actionLabel)
                                                                                    @php($permissionKey = \App\Services\AdminAccess::permissionKey($resourceKey, $actionKey))
                                                                                    <label class="permission-chip">
                                                                                        <input type="checkbox" name="permissions[]" value="{{ $permissionKey }}" {{ in_array($permissionKey, (array) ($role->permissions ?? []), true) ? 'checked' : '' }} {{ $role->is_system && $role->slug === 'admin' ? 'disabled' : '' }}>
                                                                                        <span>{{ $actionLabel }}</span>
                                                                                    </label>
                                                                                @endforeach
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endperm
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No roles found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @include('partials.table-pagination', ['paginator' => $roles])
    </div>
</div>

@perm('settings_roles.add')
<div class="modal fade" id="createRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('settings.roles.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Role Name</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Slug (Optional)</label>
                            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" placeholder="auto-created-from-name">
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="role_active" name="is_active" checked>
                                <label class="form-check-label" for="role_active">Active Role</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded-3 p-3 bg-light-subtle permission-matrix-wrap">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Permission Matrix</h6>
                                    <small class="text-muted">Module-wise access</small>
                                </div>
                                @foreach($permissionGroups as $groupKey => $group)
                                    <div class="border rounded-3 bg-white p-2 mb-2">
                                        <div class="fw-semibold text-dark mb-2">{{ $group['label'] }}</div>
                                        @foreach($group['resources'] as $resourceKey => $resource)
                                            <div class="permission-row py-2 border-top {{ $loop->first ? 'border-top-0' : '' }}">
                                                <div class="small fw-semibold text-dark mb-1">{{ $resource['label'] }}</div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach($resource['actions'] as $actionKey => $actionLabel)
                                                        @php($permissionKey = \App\Services\AdminAccess::permissionKey($resourceKey, $actionKey))
                                                        <label class="permission-chip">
                                                            <input type="checkbox" name="permissions[]" value="{{ $permissionKey }}" {{ in_array($permissionKey, old('permissions', []), true) ? 'checked' : '' }}>
                                                            <span>{{ $actionLabel }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Role</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endperm
@endsection

@push('styles')
<style>
    .permission-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.35rem 0.7rem;
        border: 1px solid rgba(67, 97, 238, 0.14);
        border-radius: 999px;
        background: #f8fafc;
        font-size: 0.82rem;
        font-weight: 600;
        color: #334155;
    }
    .permission-chip input {
        margin: 0;
    }
</style>
@endpush
