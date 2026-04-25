@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row mb-4 mt-2">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="page-title mb-0">PAN List</h4>
        </div>
    </div>

    <div class="card mt-2">
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Farmer</th>
                            <th>PAN Name</th>
                            <th>Animals Count</th>
                            <th>Animals</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pans as $key => $pan)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>{{ trim(($pan->farmer->first_name ?? '').' '.($pan->farmer->last_name ?? '')) ?: '-' }}</td>
                            <td>{{ $pan->name ?: '-' }}</td>
                            <td>{{ $pan->animals->count() }}</td>
                            <td>
                                @if($pan->animals->isEmpty())
                                    <span class="text-muted">-</span>
                                @else
                                    {{ $pan->animals->pluck('animal_name')->filter()->implode(', ') }}
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">No PAN groups found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
