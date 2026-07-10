<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $reportType->label() }}</title>
    <style>
        body {
            font-family: DejaVu Sans,
            sans-serif;
            font-size: 11px;
        }
        h1 {
            font-size: 18px;
            margin-bottom: 4px;
        }
        .meta {
            color: #555;
            margin-bottom: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th {
            background: #e5e7eb;
            padding: 6px;
            text-align: left;
            border: 1px solid #ccc;
        }
        td {
            padding: 6px;
            border: 1px solid #ccc;
        }
        .totals {
            background: #f3f4f6;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>{{ $reportType->label() }}</h1>
    <div class="meta">
        Date Range: {{ $report['start_date'] }} to {{ $report['end_date'] }}<br>
        Generated: {{ $report['generated_at'] }}
    </div>

    <table>
        <thead>
            <tr>
                @foreach(array_keys((array) $report['data']->first()) as $header)
                    <th>{{ str_replace('_', ' ', ucfirst($header)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($report['data'] as $row)
                <tr>
                    @foreach($row as $value)
                        <td>{{ is_iterable($value) ? collect($value)->implode(', ') : $value }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
