@extends('layouts.app')
@section('title', 'Admin Users')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
    @endif

    <div class="mt-4 mb-3">
        <h4 class="mb-0 text-dark">Add User</h4>
        <p class="text-muted mb-0">Create admin panel users and assign a role to control which screens they can access.</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Create User</h6>
                    @perm('settings_users.add')
                    <form method="POST" action="{{ route('settings.users.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
                                <option value="">Select role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ (string) old('role_id') === (string) $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                            @error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="user_active" name="is_active" checked>
                            <label class="form-check-label" for="user_active">Active User</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">Save User</button>
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
                    <form method="GET" action="{{ route('settings.users.index') }}" class="row g-2 mb-3">
                        @if(request('per_page'))
                            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                        @endif
                        <div class="col-md-9">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search user...">
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
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    <tr>
                                        <td>{{ $users->firstItem() + $loop->index }}</td>
                                        <td class="fw-semibold">{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary">
                                                {{ $user->role?->name ?? 'No role' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            @perm('settings_users.edit')
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUser{{ $user->id }}">Edit</button>
                                            @endperm
                                            @perm('settings_users.status')
                                            <form method="POST" action="{{ route('settings.users.toggle', $user) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-dark">
                                                    {{ $user->is_active ? 'Disable' : 'Enable' }}
                                                </button>
                                            </form>
                                            @endperm
                                        </td>
                                    </tr>

                                    @perm('settings_users.edit')
                                    <div class="modal fade" id="editUser{{ $user->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('settings.users.update', $user) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit User</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Name</label>
                                                            <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Email</label>
                                                            <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">New Password (Optional)</label>
                                                            <input type="password" name="password" class="form-control" placeholder="Leave blank to keep old password">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Role</label>
                                                            <select name="role_id" class="form-select" required>
                                                                @foreach($roles as $role)
                                                                    <option value="{{ $role->id }}" {{ (int) $user->role_id === (int) $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" value="1" id="activeUser{{ $user->id }}" name="is_active" {{ $user->is_active ? 'checked' : '' }} {{ auth()->id() === $user->id ? 'disabled' : '' }}>
                                                            <label class="form-check-label" for="activeUser{{ $user->id }}">Active User</label>
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
                                        <td colspan="6" class="text-center text-muted py-4">No users found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @include('partials.table-pagination', ['paginator' => $users])
            </div>
        </div>
    </div>
</div>
@endsection
