@extends('layouts.app')
@section('title', 'Create Role')

@section('content')
<div class="container-fluid">
    <div class="mt-4 mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="mb-0 text-dark">Create Role</h4>
            <p class="text-muted mb-0">Choose module-wise permissions for this role.</p>
        </div>
        <a href="{{ route('settings.roles.index') }}" class="btn btn-light border">
            <i class="fa-solid fa-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header role-page-header">
            <h5 class="mb-0 text-white">Create Role</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.roles.store') }}">
                @csrf
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
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ route('settings.roles.index') }}" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-success">Save Role</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .role-page-header {
        background: #448100;
    }
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
