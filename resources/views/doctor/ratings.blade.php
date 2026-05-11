@extends('layouts.app')
@section('title', 'Doctor Rating')

@section('content')
<div class="container-fluid">
    <style>
        .rating-stat-card {
            border: 0;
            overflow: hidden;
            border-radius: 18px;
            box-shadow: 0 10px 26px rgba(15, 52, 27, 0.08);
        }
        .rating-stat-card .card-body {
            padding: 18px;
        }
        .rating-star {
            color: #f6b93b;
            letter-spacing: 1px;
            font-size: 14px;
            white-space: nowrap;
        }
        .rating-score {
            min-width: 54px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 10px;
            border-radius: 999px;
            background: #fff7df;
            color: #a45f00;
            font-weight: 800;
        }
        .rating-table th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }
        .rating-table td {
            font-size: 12px;
            vertical-align: middle;
        }
    </style>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 mt-4 pt-2">
        <div>
            <h4 class="mb-1 text-dark">Doctor Rating</h4>
            <p class="text-muted mb-0">Highest rated doctors are shown first.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card rating-stat-card h-100" style="background:#ecfdf3;">
                <div class="card-body">
                    <p class="text-muted mb-1">Rated Doctors</p>
                    <h3 class="mb-0">{{ number_format($summary['rated_doctors'] ?? 0) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card rating-stat-card h-100" style="background:#fff7df;">
                <div class="card-body">
                    <p class="text-muted mb-1">Average Rating</p>
                    <h3 class="mb-0">{{ number_format((float) ($summary['average_rating'] ?? 0), 1) }}/5</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card rating-stat-card h-100" style="background:#eef6ff;">
                <div class="card-body">
                    <p class="text-muted mb-1">Total Ratings</p>
                    <h3 class="mb-0">{{ number_format($summary['total_ratings'] ?? 0) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('doctor.ratings') }}" class="row g-2 mb-3">
                @if(request('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif
                <div class="col-md-4 col-lg-3">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Search doctor, mobile, city..."
                    >
                </div>
                <div class="col-auto">
                    <button class="btn btn-success" type="submit">Search</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 rating-table">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Doctor</th>
                            <th>Contact</th>
                            <th>Degree</th>
                            <th>Clinic</th>
                            <th>City</th>
                            <th>Average Rating</th>
                            <th>Total Ratings</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($doctors as $doctor)
                            @php
                                $average = round((float) ($doctor->average_rating ?? 0), 1);
                                $filledStars = (int) round($average);
                            @endphp
                            <tr>
                                <td>{{ $doctors->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">Dr. {{ $doctor->full_name ?: '-' }}</div>
                                    <small class="text-muted">{{ $doctor->email ?: '-' }}</small>
                                </td>
                                <td>{{ $doctor->contact_number ?: '-' }}</td>
                                <td>{{ $doctor->degree ?: '-' }}</td>
                                <td>{{ $doctor->clinic_name ?: '-' }}</td>
                                <td>{{ $doctor->city ?: '-' }}</td>
                                <td>
                                    <span class="rating-score">{{ number_format($average, 1) }}</span>
                                    <span class="rating-star ms-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            {!! $i <= $filledStars ? '&#9733;' : '&#9734;' !!}
                                        @endfor
                                    </span>
                                </td>
                                <td>{{ number_format((int) ($doctor->ratings_count ?? 0)) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No doctor ratings found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @include('partials.table-pagination', ['paginator' => $doctors])
    </div>
</div>
@endsection
