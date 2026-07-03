@php
    $tablePaginator = $paginator ?? null;
    $perPageName = $perPageName ?? 'per_page';
    $perPageOptions = $perPageOptions ?? [10, 25, 50, 100];
    $currentPerPage = (int) request($perPageName, $tablePaginator?->perPage() ?? 10);
@endphp

@if($tablePaginator && $tablePaginator->total() > 0)
    <div class="card-footer bg-white corzin-server-pagination">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2 text-muted small">
                <span>Show</span>
                <select class="form-select form-select-sm corzin-server-per-page" data-per-page-name="{{ $perPageName }}" style="width: 82px;">
                    @foreach($perPageOptions as $option)
                        <option value="{{ $option }}" {{ $currentPerPage === (int) $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
                <span>entries</span>
                <span class="ms-2">
                    Showing {{ $tablePaginator->firstItem() }} to {{ $tablePaginator->lastItem() }} of {{ $tablePaginator->total() }}
                </span>
            </div>

            @if($tablePaginator->hasPages())
                <div>
                    {{ $tablePaginator->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
    </div>
@endif
