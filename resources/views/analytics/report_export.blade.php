<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; margin: 24px; }
        h1 { margin-bottom: 4px; }
        .meta { color: #6b7280; margin-bottom: 18px; }
        .filters, .kpis, .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .filters th, .filters td, .kpis th, .kpis td, .data-table th, .data-table td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; }
        .filters th, .kpis th, .data-table th { background: #f3f4f6; }
        .section-title { margin: 20px 0 10px; font-size: 18px; }
        .kpi-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-bottom: 20px; }
        .kpi-card { border: 1px solid #d1d5db; padding: 10px 12px; border-radius: 8px; }
        .kpi-card small { display: block; color: #6b7280; margin-bottom: 6px; }
        .kpi-card strong { font-size: 18px; }
        @media print {
            body { margin: 12px; }
        }
    </style>
</head>
<body>
    <h1>{{ $reportTitle }}</h1>
    <div class="meta">Generated at {{ $generatedAt }}</div>

    @if(!empty($filtersSummary))
        <div class="section-title">Applied Filters</div>
        <table class="filters">
            <thead>
                <tr><th>Filter</th><th>Value</th></tr>
            </thead>
            <tbody>
                @foreach($filtersSummary as $filter)
                    <tr>
                        <td>{{ $filter['label'] }}</td>
                        <td>{{ $filter['value'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(!empty($kpis))
        <div class="section-title">KPI Summary</div>
        <div class="kpi-grid">
            @foreach($kpis as $kpi)
                <div class="kpi-card">
                    <small>{{ $kpi['label'] }}</small>
                    <strong>{{ $kpi['value'] }}</strong>
                </div>
            @endforeach
        </div>
    @endif

    @foreach($tables as $table)
        <div class="section-title">{{ $table['title'] }}</div>
        <table class="data-table">
            <thead>
                <tr>
                    @foreach($table['columns'] as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($table['rows'] as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($table['columns']) }}">No data available.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    @if($isPdf)
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
</body>
</html>
