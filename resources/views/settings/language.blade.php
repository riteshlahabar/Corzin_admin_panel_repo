@extends('layouts.app')
@section('title', 'Language')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
    @endif

    <div class="mt-4 mb-3">
        <h4 class="mb-0 text-dark">Language</h4>
    </div>

    @if(! $tableReady)
        <div class="alert alert-warning border-0 shadow-sm">
            Please run migration for <strong>app_translations</strong> table first, then this screen will start working.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('settings.language.index') }}" class="row g-2 mb-3">
                        @if(request('per_page'))
                            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                        @endif
                        <div class="col-md-10">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search translation values...">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-success">Search</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>English</th>
                                    <th>Hindi</th>
                                    <th>Marathi</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($translations as $translation)
                                    <tr>
                                        <td>{{ $translations->firstItem() + $loop->index }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($translation->en_value, 50) }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($translation->hi_value, 50) }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($translation->mr_value, 50) }}</td>
                                        <td>
                                            <span class="badge {{ $translation->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $translation->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            @perm('settings_language.edit')
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editTranslation{{ $translation->id }}">
                                                Edit
                                            </button>
                                            @endperm
                                            @perm('settings_language.status')
                                            <form method="POST" action="{{ route('settings.language.toggle', $translation) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-dark">
                                                    {{ $translation->is_active ? 'Disable' : 'Enable' }}
                                                </button>
                                            </form>
                                            @endperm

                                            @perm('settings_language.edit')
                                            <div class="modal fade" id="editTranslation{{ $translation->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <form method="POST" action="{{ route('settings.language.update', $translation) }}">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit Translation</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body row g-3">
                                                                <div class="col-md-6">
                                                                    <label class="form-label">Group</label>
                                                                    <input type="text" name="group_name" class="form-control" value="{{ $translation->group_name }}" required>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label">Translation Key</label>
                                                                    <input type="text" name="translation_key" class="form-control" value="{{ $translation->translation_key }}" required>
                                                                </div>
                                                                <div class="col-12">
                                                                    <label class="form-label">English</label>
                                                                    <textarea name="en_value" rows="2" class="form-control">{{ $translation->en_value }}</textarea>
                                                                </div>
                                                                <div class="col-12">
                                                                    <label class="form-label">Hindi</label>
                                                                    <textarea name="hi_value" rows="2" class="form-control">{{ $translation->hi_value }}</textarea>
                                                                </div>
                                                                <div class="col-12">
                                                                    <label class="form-label">Marathi</label>
                                                                    <textarea name="mr_value" rows="2" class="form-control">{{ $translation->mr_value }}</textarea>
                                                                </div>
                                                                <div class="col-12">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" value="1" id="translation_active_{{ $translation->id }}" name="is_active" {{ $translation->is_active ? 'checked' : '' }}>
                                                                        <label class="form-check-label" for="translation_active_{{ $translation->id }}">Active</label>
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
                                            @endperm
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No translations added yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($tableReady && $translations instanceof \Illuminate\Contracts\Pagination\Paginator)
                    @include('partials.table-pagination', ['paginator' => $translations])
                @endif
            </div>
        </div>
    </div>
</div>
@endsection