@extends('layouts.app')
@section('title', 'Backup Data')

@section('content')
<div class="container-fluid">
    <div class="mt-4 mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="mb-0 text-dark">Backup Data</h4>
            <p class="text-muted mb-0">Download the current database as an SQL backup file.</p>
        </div>
        @perm('settings_backup.export')
        <a href="{{ route('settings.backup.download') }}" class="btn btn-success">
            <i class="fa-solid fa-download me-1"></i> Download Backup
        </a>
        @endperm
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h6 class="fw-semibold mb-1">Backup Download History</h6>
                    <p class="text-muted mb-0 small">Suggested columns: file name, format, downloaded by, tables count, file size, downloaded at.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Backup File</th>
                            <th>Format</th>
                            <th>Downloaded By</th>
                            <th class="text-end">Tables</th>
                            <th class="text-end">File Size</th>
                            <th>Downloaded At</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($backupDownloads as $download)
                            <tr>
                                <td>{{ $backupDownloads->firstItem() + $loop->index }}</td>
                                <td><code>{{ $download->file_name }}</code></td>
                                <td><span class="badge bg-success-subtle text-success text-uppercase">{{ $download->backup_format }}</span></td>
                                <td>{{ $download->user?->name ?: '-' }}</td>
                                <td class="text-end">{{ number_format($download->tables_count) }}</td>
                                <td class="text-end">{{ number_format($download->file_size_bytes / 1024, 2) }} KB</td>
                                <td>{{ optional($download->downloaded_at)->format('d M Y h:i A') ?: '-' }}</td>
                                <td>{{ $download->notes ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No backup download history found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @include('partials.table-pagination', ['paginator' => $backupDownloads])
    </div>
</div>
@endsection
