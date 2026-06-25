@extends('layouts.app')
@section('title', 'Backup Data')

@section('content')
<div class="container-fluid">
    <div class="mt-4 mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="mb-0 text-dark">Backup Data</h4>
            <p class="text-muted mb-0">Download the current admin database data as a JSON backup file.</p>
        </div>
        @perm('settings_backup.export')
        <a href="{{ route('settings.backup.download') }}" class="btn btn-success">
            <i class="fa-solid fa-download me-1"></i> Download Backup
        </a>
        @endperm
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Backup Information</h6>
                    <div class="mb-2"><span class="text-muted">Generated Preview:</span> <strong>{{ $generatedAt->format('d M Y h:i A') }}</strong></div>
                    <div class="mb-2"><span class="text-muted">Tables Included:</span> <strong>{{ $tableSummaries->count() }}</strong></div>
                    <div class="mb-3"><span class="text-muted">Format:</span> <strong>JSON</strong></div>
                    <div class="alert alert-light border mb-0">
                        This backup includes database tables and rows available in the current project database.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Included Tables</h6>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Table Name</th>
                                    <th class="text-end">Rows</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tableSummaries as $table)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><code>{{ $table['name'] }}</code></td>
                                        <td class="text-end">{{ number_format($table['rows']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No tables found for backup.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
