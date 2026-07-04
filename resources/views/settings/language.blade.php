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
        <div class="col-12" id="translationContentArea">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form id="translationSearchForm" method="GET" action="{{ route('settings.language.index') }}" class="d-flex flex-wrap justify-content-end align-items-center gap-2 mb-3">
                        @if(request('per_page'))
                            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                        @endif
                        <div style="width: min(180px, 100%);">
                            <select id="translationSearchMode" name="search_mode" class="form-select form-select-sm">
                                <option value="single_keyword" {{ request('search_mode') === 'single_keyword' ? 'selected' : '' }}>Single Keyword</option>
                                <option value="phrase" {{ request('search_mode', 'phrase') === 'phrase' ? 'selected' : '' }}>Phrase</option>
                            </select>
                        </div>
                        <div style="width: min(320px, 100%);">
                            <input
                                type="text"
                                id="translationSearchInput"
                                name="search"
                                value="{{ request('search') }}"
                                class="form-control form-control-sm"
                                placeholder="Search translation values..."
                                autocomplete="off"
                            >
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
                                            <div class="d-flex flex-nowrap align-items-center gap-2">
                                                @perm('settings_language.edit')
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editTranslation{{ $translation->id }}">
                                                    Edit
                                                </button>
                                                @endperm

                                                @perm('settings_language.status')
                                                <form method="POST" action="{{ route('settings.language.toggle', $translation) }}" class="m-0">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-dark text-nowrap">
                                                        {{ $translation->is_active ? 'Disable' : 'Enable' }}
                                                    </button>
                                                </form>
                                                @endperm
                                            </div>

                                            @perm('settings_language.edit')
                                            <div class="modal fade" id="editTranslation{{ $translation->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <form method="POST" action="{{ route('settings.language.update', $translation) }}">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="modal-header bg-success text-white">
                                                                <h5 class="modal-title text-white">Edit Translation</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body row g-3">

                                                                <div class="col-12">
                                                                    <label class="form-label">English</label>
                                                                    <textarea name="en_value" rows="2" class="form-control bg-light" readonly>{{ $translation->en_value }}</textarea>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const areaSelector = '#translationContentArea';
        let timer;
        let activeController = null;

        const bindLanguageInteractions = function () {
            const area = document.querySelector(areaSelector);
            const form = document.getElementById('translationSearchForm');
            const input = document.getElementById('translationSearchInput');
            const modeSelect = document.getElementById('translationSearchMode');
            const perPage = area ? area.querySelector('.corzin-server-per-page') : null;
            const paginationLinks = area ? area.querySelectorAll('.pagination a.page-link') : [];

            if (form && input) {
                let lastRequested = input.value.trim();
                let lastRequestedMode = modeSelect ? modeSelect.value : 'phrase';

                const requestSearch = function (force = false) {
                    const rawValue = input.value;
                    const trimmedValue = rawValue.trim();
                    const currentMode = modeSelect ? modeSelect.value : 'phrase';

                    if (!force && rawValue.endsWith(' ')) {
                        return;
                    }

                    if (!force && trimmedValue === lastRequested && currentMode === lastRequestedMode) {
                        return;
                    }

                    lastRequested = trimmedValue;
                    lastRequestedMode = currentMode;
                    const url = new URL(form.action, window.location.origin);
                    const params = new FormData(form);
                    params.set('search', rawValue);
                    url.search = new URLSearchParams(params).toString();
                    loadTranslationArea(url.toString(), true);
                };

                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    clearTimeout(timer);
                    requestSearch(true);
                });

                input.addEventListener('input', function () {
                    clearTimeout(timer);
                    timer = setTimeout(function () {
                        requestSearch(false);
                    }, 700);
                });

                input.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        clearTimeout(timer);
                        requestSearch(true);
                    }
                });

                if (modeSelect) {
                    modeSelect.addEventListener('change', function () {
                        clearTimeout(timer);
                        requestSearch(true);
                    });
                }
            }

            if (perPage) {
                perPage.addEventListener('change', function () {
                    const formData = new FormData(form || document.createElement('form'));
                    formData.set('per_page', perPage.value);
                    if (input) {
                        formData.set('search', input.value);
                    }
                    const url = new URL((form && form.action) || window.location.href, window.location.origin);
                    url.search = new URLSearchParams(formData).toString();
                    loadTranslationArea(url.toString(), true);
                });
            }

            paginationLinks.forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    const href = link.getAttribute('href');
                    if (!href) {
                        return;
                    }
                    loadTranslationArea(href, true);
                });
            });
        };

        const loadTranslationArea = async function (url, pushState) {
            const area = document.querySelector(areaSelector);
            if (!area) {
                return;
            }

            if (activeController) {
                activeController.abort();
            }

            activeController = new AbortController();
            area.style.opacity = '0.55';
            area.style.pointerEvents = 'none';

            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: activeController.signal,
                });

                if (!response.ok) {
                    throw new Error('Request failed');
                }

                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const nextArea = doc.querySelector(areaSelector);

                if (!nextArea) {
                    throw new Error('Updated content not found');
                }

                area.outerHTML = nextArea.outerHTML;

                if (pushState) {
                    window.history.replaceState({}, '', url);
                }

                bindLanguageInteractions();
            } catch (error) {
                if (error.name !== 'AbortError') {
                    window.location.href = url;
                }
            } finally {
                const refreshedArea = document.querySelector(areaSelector);
                if (refreshedArea) {
                    refreshedArea.style.opacity = '1';
                    refreshedArea.style.pointerEvents = 'auto';
                }
            }
        };

        bindLanguageInteractions();
    });
</script>
@endpush



