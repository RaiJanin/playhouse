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
            margin: 0;
        }
        h1 {
            font-size: 18px;
            margin-bottom: 4px;
        }
        .header {
            border-collapse: collapse;
            border: none;
        }
        table.header td,
        table.header th {
            border: none;
        }
        .meta {
            color: #555;
            margin-bottom: 12px;
        }
        .report-title {
            text-align: right;
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
        .numbers th {
            background: #f4f4f5;
        }
        table.data-table tr {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    @php
        $rows = $report['data'];
        $headers = $rows->isNotEmpty() ? array_keys((array) $rows->first()) : [];
        $numericHeaders = ['transaction_count', 'total_items', 'total_sales', 'overall_duration', 'total_guardian', 'total_socks'];

        $isStructuredList = function ($value) {
            if (!is_iterable($value)) {
                return false;
            }
            $collection = collect($value);
            if ($collection->isEmpty()) {
                return false;
            }
            return $collection->every(fn ($item) => is_array($item) || is_object($item));
        };
    @endphp
    <table class="header">
        <tbody>
            <tr>
                <td>
                    <h1>Mimo Play Cafe</h1>
                </td>
                <td>
                    <h1 class="report-title">{{ $reportType->label() }}</h1>
                </td>
            </tr>
        </tbody>
    </table>
    <div class="meta">
        Date Range: {{ $report['start_date'] }} to {{ $report['end_date'] }}<br>
        Generated: {{ $report['generated_at'] }}
    </div>

    <table class="data-table">
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th class="{{ in_array($header, $numericHeaders) ? 'text-right' : '' }}">
                        {{ str_replace('_', ' ', ucfirst($header)) }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $key => $value)
                        <td class="{{ in_array($key, $numericHeaders) ? 'text-right' : '' }}">
                            @if ($isStructuredList($value))
                                <ul class="duration-list">
                                    @foreach($value as $item)
                                        @php $item = (array) $item; @endphp
                                        <li>
                                            <span>{{ $item[array_key_first($item)] }}</span>
                                            <span>
                                                {{ collect($item)->except(array_key_first($item))->implode(', ') }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @elseif (is_iterable($value))
                                {{ collect($value)->implode(' | ') ?: '—' }}
                            @else
                                {{ $value !== '' && $value !== null ? $value : '—' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(count($headers), 1) }}" class="no-data">
                        No data found for the selected period.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <br><br><br>
    <table>
        <thead>
            <tr>
                <th>Transactions</th>
                <th>Total Sales</th>
                <th>Total Duration</th>
                <th>Total Socks</th>
            </tr>
            <tr class="numbers">
                <th>{{ number_format( $report['totals']['total_transactions'] ?? 0) }}</th>
                <th>₱{{ number_format($report['totals']['total_sales'] ?? 0, 2) }}</th>
                <th>{{ number_format($report['totals']['total_duration'] ?? 0, 2) }} ₱/hour</th>
                <th>{{ number_format($report['totals']['total_socks']) }}</th>
            </tr>
        </thead>
    </table>
    <br>
    <small>Reports generated by: <italic>Right Apps Incorporated</italic></small>
</body>
</html>
