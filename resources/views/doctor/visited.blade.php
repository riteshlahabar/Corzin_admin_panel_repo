@extends('layouts.app')
@section('title', 'Visited')

@section('content')
<div class="container-fluid">
    <style>
        .doctor-table thead th {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            white-space: nowrap;
            padding-top: 0.7rem;
            padding-bottom: 0.7rem;
        }
        .doctor-table tbody td {
            font-size: 11px;
            vertical-align: middle;
            padding-top: 0.6rem;
            padding-bottom: 0.6rem;
        }
    </style>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 mt-4 pt-2">
        <h4 class="page-title mb-0">Visited</h4>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form id="visitedSearchForm" method="GET" action="{{ route('doctor.visited') }}" class="row g-2 mb-3 align-items-center">
                @if(request('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif
                <div class="col-md-3 col-lg-2">
                    <select id="visitedSearchField" name="search_field" class="form-select">
                        <option value="all" {{ request('search_field', 'all') === 'all' ? 'selected' : '' }}>All</option>
                        <option value="farmer_name" {{ request('search_field') === 'farmer_name' ? 'selected' : '' }}>Farmer Name</option>
                        <option value="doctor_name" {{ request('search_field') === 'doctor_name' ? 'selected' : '' }}>Doctor Name</option>
                        <option value="animal_name" {{ request('search_field') === 'animal_name' ? 'selected' : '' }}>Animal Name</option>
                        <option value="concern" {{ request('search_field') === 'concern' ? 'selected' : '' }}>Concern</option>
                        <option value="medicine" {{ request('search_field') === 'medicine' ? 'selected' : '' }}>Medicine</option>
                        <option value="onsite_treatment" {{ request('search_field') === 'onsite_treatment' ? 'selected' : '' }}>On Site Treatment</option>
                        <option value="completed_date" {{ request('search_field') === 'completed_date' ? 'selected' : '' }}>Completed Date</option>
                        <option value="charges" {{ request('search_field') === 'charges' ? 'selected' : '' }}>Charges</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-3">
                    <input
                        id="visitedSearchInput"
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        class="form-control"
                        placeholder="Search selected field..."
                    >
                </div>
                <div class="col-md-2 col-lg-2">
                    <input type="date" id="visitedFromDate" name="from_date" value="{{ request('from_date') }}" class="form-control">
                </div>
                <div class="col-md-2 col-lg-2">
                    <input type="date" id="visitedToDate" name="to_date" value="{{ request('to_date') }}" class="form-control">
                </div>
                <div class="col-md-auto ms-lg-auto d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-light border" onclick="exportTableToPdf('visitedTableExport', 'Visited Records')" title="Download PDF">
                        <i class="fa-solid fa-file-pdf text-danger"></i>
                    </button>
                    <button type="button" class="btn btn-light border" onclick="exportTableToExcel('visitedTableExport', 'visited-records')" title="Download Excel">
                        <i class="fa-solid fa-file-excel text-success"></i>
                    </button>
                    <a href="{{ route('doctor.visited') }}" class="btn btn-light border">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 doctor-table" id="visitedTableExport">
                    <thead>
                        <tr>
                            <th>Appointment ID</th>
                            <th>Doctor</th>
                            <th>Farmer</th>
                            <th>Animal</th>
                            <th>Concern</th>
                            <th>Medicine</th>
                            <th>On-Site Treatment</th>
                            <th>Completed Date</th>
                            <th>Charges</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($visits as $visit)
                            @php
                                $farmerFullName = trim(implode(' ', array_filter([
                                    optional($visit->farmer)->first_name,
                                    optional($visit->farmer)->middle_name,
                                    optional($visit->farmer)->last_name,
                                ])));

                                $medicineSummary = '-';
                                $treatmentDetails = $visit->treatment_details;
                                if (is_string($treatmentDetails)) {
                                    $decoded = json_decode($treatmentDetails, true);
                                    if (json_last_error() === JSON_ERROR_NONE) {
                                        $treatmentDetails = $decoded;
                                    }
                                }

                                if (is_array($treatmentDetails)) {
                                    $medicineRows = [];
                                    $medicineSource = $treatmentDetails['medicines'] ?? $treatmentDetails;
                                    if (is_array($medicineSource)) {
                                        foreach ($medicineSource as $item) {
                                            if (is_array($item)) {
                                                $name = trim((string) ($item['medicine'] ?? $item['name'] ?? ''));
                                                $qty = trim((string) ($item['total'] ?? $item['tabs'] ?? $item['quantity'] ?? ''));
                                                $line = trim($name.($qty !== '' ? ' ('.$qty.')' : ''));
                                                if ($line !== '') {
                                                    $medicineRows[] = $line;
                                                }
                                            } elseif (is_string($item) && trim($item) !== '') {
                                                $medicineRows[] = trim($item);
                                            }
                                        }
                                    }
                                    if (! empty($medicineRows)) {
                                        $medicineSummary = implode(', ', array_slice($medicineRows, 0, 4));
                                    }
                                } elseif (is_string($treatmentDetails) && trim($treatmentDetails) !== '') {
                                    $medicineSummary = trim($treatmentDetails);
                                }
                            @endphp
                            <tr>
                                <td><span class="fw-semibold">{{ $visit->appointment_code }}</span></td>
                                <td>{{ $visit->doctor->full_name ?: $visit->doctor->name ?: '-' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $farmerFullName !== '' ? $farmerFullName : ($visit->farmer_name ?: '-') }}</div>
                                    <small class="text-muted">{{ $visit->farmer_phone ?: '-' }}</small>
                                </td>
                                <td>{{ $visit->animal_name ?: '-' }}</td>
                                <td style="min-width:220px;">{{ $visit->concern ?: '-' }}</td>
                                <td style="min-width:220px;">{{ $medicineSummary }}</td>
                                <td style="min-width:220px;">{{ $visit->onsite_treatment ?: '-' }}</td>
                                <td>{{ optional($visit->completed_at ?: $visit->updated_at)->format('d-m-Y h:i A') ?: '-' }}</td>
                                <td>{{ $visit->charges !== null ? 'Rs '.number_format((float) $visit->charges, 2) : '-' }}</td>
                                <td><span class="badge bg-success">Completed</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No visited records found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @include('partials.table-pagination', ['paginator' => $visits])
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('visitedSearchInput');
    const field = document.getElementById('visitedSearchField');
    const fromDate = document.getElementById('visitedFromDate');
    const toDate = document.getElementById('visitedToDate');
    const form = document.getElementById('visitedSearchForm');
    if (!form) return;

    let timer = null;
    if (input) {
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                form.submit();
            }, 350);
        });
    }

    [field, fromDate, toDate].forEach(function (element) {
        if (!element) return;
        element.addEventListener('change', function () {
            form.submit();
        });
    });
});

function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const csv = [];
    table.querySelectorAll('tr').forEach((row) => {
        const cols = row.querySelectorAll('th, td');
        const rowData = [];

        cols.forEach((col) => {
            const text = (col.innerText || '').replace(/\n/g, ' ').replace(/,/g, ' ').trim();
            rowData.push('"' + text + '"');
        });

        csv.push(rowData.join(','));
    });

    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const downloadLink = document.createElement('a');
    const url = URL.createObjectURL(csvFile);
    downloadLink.href = url;
    downloadLink.download = filename + '.csv';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

function exportTableToPdf(tableId, title) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>${title}</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 24px; }
                h2 { margin-bottom: 16px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ccc; padding: 8px; font-size: 12px; text-align: left; }
            </style>
        </head>
        <body>
            <h2>${title}</h2>
            ${table.outerHTML}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
}
</script>
@endsection
