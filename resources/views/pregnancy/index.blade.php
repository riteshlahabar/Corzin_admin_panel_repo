@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row g-3 mb-4 mt-2">
        <div class="col-md-3">
            <div class="card bg-primary-subtle border-0"><div class="card-body"><p class="text-muted mb-1">Total Records</p><h3 class="mb-0">{{ $summary['total'] }}</h3></div></div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success-subtle border-0"><div class="card-body"><p class="text-muted mb-1">Current</p><h3 class="mb-0">{{ $summary['current'] }}</h3></div></div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning-subtle border-0"><div class="card-body"><p class="text-muted mb-1">Pregnant</p><h3 class="mb-0">{{ $summary['pregnant'] }}</h3></div></div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info-subtle border-0"><div class="card-body"><p class="text-muted mb-1">Calved</p><h3 class="mb-0">{{ $summary['calved'] }}</h3></div></div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">Cow Pregnancy / Reproduction</h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <select id="pregnancySearchField" class="form-select" style="width:220px;">
                    <option value="all">All Columns</option>
                    <option value="farmer">Farmer</option>
                    <option value="cow">Cow</option>
                    <option value="pregnancy-no">Pregnancy No</option>
                    <option value="service-no">Service No</option>
                    <option value="ai-date">AI Date</option>
                    <option value="check-due">Check Due</option>
                    <option value="expected-calving">Expected Calving</option>
                    <option value="result">Result</option>
                    <option value="status">Status</option>
                    <option value="current">Current</option>
                    <option value="notes">Notes</option>
                </select>
                <input type="text" id="pregnancySearch" class="form-control" placeholder="Search selected field..." style="width:280px;">
                <button type="button" class="btn btn-light border" onclick="exportTableToPdf('pregnancyTableExport', 'Pregnancy Records')" title="Download PDF">
                    <i class="fa-solid fa-file-pdf text-danger"></i>
                </button>
                <button type="button" class="btn btn-light border" onclick="exportTableToExcel('pregnancyTableExport', 'pregnancy-records')" title="Download Excel">
                    <i class="fa-solid fa-file-excel text-success"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table mb-0" id="pregnancyTableExport">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Farmer</th>
                            <th>Cow</th>
                            <th>Pregnancy No</th>
                            <th>Service No</th>
                            <th>AI Date</th>
                            <th>Check Due</th>
                            <th>Expected Calving</th>
                            <th>Result</th>
                            <th>Status</th>
                            <th>Current</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($records as $key => $record)
                        @php
                            $farmerName = trim(($record->farmer->first_name ?? '').' '.($record->farmer->last_name ?? ''));
                            $animalName = trim(($record->animal->animal_name ?? '').' '.(!empty($record->animal->tag_number) ? '(Tag: '.$record->animal->tag_number.')' : ''));
                            $pregnancyNo = (string) ($record->pregnancy_no ?? '-');
                            $serviceNo = (string) ($record->service_no ?? '-');
                            $aiDate = optional($record->ai_date)->format('d-m-Y') ?: '-';
                            $checkDue = optional($record->pregnancy_check_due_date)->format('d-m-Y') ?: '-';
                            $expectedCalving = optional($record->expected_calving_date)->format('d-m-Y') ?: '-';
                            $result = str_replace('_', ' ', ucfirst($record->pregnancy_result));
                            $status = str_replace('_', ' ', ucfirst($record->status));
                            $current = $record->is_current ? 'Yes' : 'No';
                            $notes = $record->notes ?: '-';
                            $allSearch = strtolower(implode(' ', [
                                $farmerName,
                                $animalName,
                                $pregnancyNo,
                                $serviceNo,
                                $aiDate,
                                $checkDue,
                                $expectedCalving,
                                $result,
                                $status,
                                $current,
                                $notes,
                            ]));
                        @endphp
                        <tr class="pregnancy-row"
                            data-all="{{ $allSearch }}"
                            data-farmer="{{ strtolower($farmerName) }}"
                            data-cow="{{ strtolower($animalName) }}"
                            data-pregnancy-no="{{ strtolower($pregnancyNo) }}"
                            data-service-no="{{ strtolower($serviceNo) }}"
                            data-ai-date="{{ strtolower($aiDate) }}"
                            data-check-due="{{ strtolower($checkDue) }}"
                            data-expected-calving="{{ strtolower($expectedCalving) }}"
                            data-result="{{ strtolower($result) }}"
                            data-status="{{ strtolower($status) }}"
                            data-current="{{ strtolower($current) }}"
                            data-notes="{{ strtolower($notes) }}">
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $farmerName ?: '-' }}</td>
                            <td>{{ $animalName ?: '-' }}</td>
                            <td>{{ $record->pregnancy_no }}</td>
                            <td>{{ $record->service_no }}</td>
                            <td>{{ $aiDate }}</td>
                            <td>{{ $checkDue }}</td>
                            <td>{{ $expectedCalving }}</td>
                            <td><span class="badge bg-light text-dark">{{ $result }}</span></td>
                            <td><span class="badge bg-success-subtle text-success">{{ $status }}</span></td>
                            <td>{{ $current }}</td>
                            <td>{{ $notes }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="text-center text-muted py-4">No pregnancy records found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('pregnancySearch');
    const field = document.getElementById('pregnancySearchField');
    if (!input || !field) return;

    function filterRows() {
        const q = input.value.toLowerCase().trim();
        const selectedField = field.value;

        document.querySelectorAll('.pregnancy-row').forEach((row) => {
            const haystack = selectedField === 'all'
                ? (row.getAttribute('data-all') || '')
                : (row.getAttribute('data-' + selectedField) || '');
            row.style.display = haystack.includes(q) ? '' : 'none';
        });
    }

    input.addEventListener('input', filterRows);
    field.addEventListener('change', filterRows);
});

function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const csv = [];
    table.querySelectorAll('tr').forEach((row) => {
        if (row.style.display === 'none') return;
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

    const clonedTable = table.cloneNode(true);
    clonedTable.querySelectorAll('tbody tr').forEach((row, index) => {
        const sourceRow = table.querySelectorAll('tbody tr')[index];
        if (sourceRow && sourceRow.style.display === 'none') {
            row.remove();
        }
    });

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
            ${clonedTable.outerHTML}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
}
</script>
@endsection
