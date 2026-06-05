# Report Module – Complete Implementation Guide

## Initial Load Behavior

Every dashboard button (`<x-reports-link-btn>`) links directly to a report URL with **no query parameters**:
- `/admin-panel/reports/mR001` → Outlet Sales
- `/admin-panel/reports/mR002` → Cashier
- `/admin-panel/reports/mR003` → Hour Sales
- `/admin-panel/reports/mR004` → Item Sales

On first load:
1. `ReportRequest::prepareForValidation()` injects **today's date** for both `start_date` and `end_date`.
2. The service queries with today's range.
3. The report table renders **immediately** with real data — no filter submission needed.

The filter bar is present but **inactive until the user clicks "Apply Filter"**. Changing date inputs without submitting does not re-query.

| User Action | What Happens |
|-------------|--------------|
| Click dashboard button | Report loads with today's data (default dates) |
| Change dates, click **Apply Filter** | Form submits `GET` with query params → table refreshes |
| Click **Close** | Returns to dashboard |
| Click **Export PDF / CSV** | Downloads file for current date range |

---

## 1. ReportRequest (Validation)

**File:** `app/Http/Requests/ReportRequest.php`

Create this file. It validates all incoming report requests using the same FormRequest pattern used by `SmsBlastRequest`, `ProfileUpdateRequest`, etc.

**Important:** `prepareForValidation()` defaults `start_date` and `end_date` to **today** when neither is provided. This means every report link from the dashboard loads immediately with today's data — no filter submission required. The filter form is only used to narrow the date range afterwards.

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\MimoReport;
use Carbon\Carbon;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reportTypes = implode(',', array_column(MimoReport::cases(), 'value'));

        return [
            'mimo_report' => "required|string|in:{$reportTypes}",
            'start_date'  => 'required|date_format:Y-m-d',
            'end_date'    => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ];
    }

    public function messages(): array
    {
        return [
            'mimo_report.required'      => 'Report type is required.',
            'mimo_report.in'            => 'Invalid report type selected.',
            'start_date.required'       => 'Start date is required.',
            'start_date.date_format'    => 'Start date must be YYYY-MM-DD.',
            'end_date.required'         => 'End date is required.',
            'end_date.date_format'      => 'End date must be YYYY-MM-DD.',
            'end_date.after_or_equal'   => 'End date must be on or after start date.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'start_date' => $this->input('start_date', now()->format('Y-m-d')),
            'end_date'   => $this->input('end_date', now()->format('Y-m-d')),
        ]);
    }
}
```

### Behavior Explanation

| Scenario | Result |
|----------|--------|
| User clicks `Outlet Sales` dashboard button → `GET /admin-panel/reports/mR001` | `start_date` and `end_date` default to **today**. Report loads immediately with today's data. |
| User changes dates and clicks **Apply Filter** | Form submits `GET` back to the same URL with `?start_date=...&end_date=...`. ReportService re-queries and the table refreshes. |
| User clicks a dashboard button while already on a report page | Fresh load with default (today) dates — previous filter is discarded. |

---

## 2. ReportService (Business Logic)

**File:** `app/Services/ReportService.php`

Replace the current stub entirely with this implementation.

```php
<?php

namespace App\Services;

use App\Enums\MimoReport;
use App\Models\OrderItems;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Main entry point for viewing reports.
     * Returns data array suitable for Blade templates.
     */
    public function getReport(Request $request, $mimo_report): array
    {
        $reportType = $this->resolveReportType($mimo_report);
        $startDate  = $this->resolveDate($request, 'start_date');
        $endDate    = $this->resolveDate($request, 'end_date');

        $result = match ($reportType) {
            MimoReport::OUTLET_SALES => $this->outletSalesReport($startDate, $endDate),
            MimoReport::CASHIER      => $this->cashierReport($startDate, $endDate, $request),
            MimoReport::HOUR_SALES   => $this->hourSalesReport($startDate, $endDate),
            MimoReport::ITEM_SALES   => $this->itemSalesReport($startDate, $endDate),
        };

        return [
            'data'    => $result['data'],
            'totals'  => $result['totals'],
            'type'    => $reportType,
            'start'   => $startDate->format('Y-m-d'),
            'end'     => $endDate->format('Y-m-d'),
        ];
    }

    /**
     * Entry point for exports (PDF/CSV).
     * Returns flat array without totals row to keep exports clean.
     */
    public function generateReport(Request $request, $mimo_report): array
    {
        $reportType = $this->resolveReportType($mimo_report);
        $startDate  = $this->resolveDate($request, 'start_date');
        $endDate    = $this->resolveDate($request, 'end_date');

        $result = match ($reportType) {
            MimoReport::OUTLET_SALES => $this->outletSalesReport($startDate, $endDate),
            MimoReport::CASHIER      => $this->cashierReport($startDate, $endDate, $request),
            MimoReport::HOUR_SALES   => $this->hourSalesReport($startDate, $endDate),
            MimoReport::ITEM_SALES   => $this->itemSalesReport($startDate, $endDate),
        };

        return [
            'data'        => $result['data'],
            'report_type' => $reportType->label(),
            'start_date'  => $startDate->format('Y-m-d'),
            'end_date'    => $endDate->format('Y-m-d'),
            'generated_at'=> now()->format('Y-m-d H:i:s'),
        ];
    }

    // =============================================
    //  PRIVATE: Report type resolution & helpers
    // =============================================

    private function resolveReportType($mimo_report): MimoReport
    {
        $report = MimoReport::tryFrom($mimo_report);
        if (!$report) {
            throw new \InvalidArgumentException("Invalid report type: {$mimo_report}");
        }
        return $report;
    }

    private function resolveDate(Request $request, string $key): Carbon
    {
        $dateStr = $request->query($key, now()->format('Y-m-d'));
        return Carbon::parse($dateStr);
    }

    private function baseQuery($startDate, $endDate)
    {
        return OrderItems::query()
            ->whereBetween('created_at', [$startDate, $endDate->endOfDay()])
            ->whereNotNull('subtotal');
    }

    private function buildTotals($items): array
    {
        return [
            'total_transactions' => $items->count(),
            'total_sales'        => $items->sum('subtotal'),
            'total_duration'     => $items->sum('durationsubtotal'),
            'total_socks'        => $items->sum('socksqty'),
        ];
    }

    // =============================================
    //  PRIVATE: Individual report queries
    // =============================================

    /**
     * mR001 – Group by outlet (parentPl.d_code / d_name)
     */
    private function outletSalesReport($startDate, $endDate): array
    {
        $items = $this->baseQuery($startDate, $endDate)
            ->with(['order.parentPl'])
            ->get();

        $grouped = $items->groupBy(function ($item) {
            return $item->order?->parentPl?->d_code ?? 'UNKNOWN';
        })->map(function ($group) {
            $parent = $group->first()->order?->parentPl;
            return [
                'outlet_code'        => $parent?->d_code ?? 'UNKNOWN',
                'outlet'             => $parent?->d_name ?? 'UNKNOWN',
                'transaction_count'  => $group->count(),
                'total_items'        => $group->count(),
                'total_sales'        => $group->sum('subtotal'),
                'total_duration'     => $group->sum('durationsubtotal'),
                'total_socks'        => $group->sum('socksqty'),
            ];
        })->values();

        return ['data' => $grouped, 'totals' => $this->buildTotals($items)];
    }

    /**
     * mR002 – Group by guardian / outlet. Supports guardian filter from query string.
     */
    private function cashierReport($startDate, $endDate, $request): array
    {
        $items = $this->baseQuery($startDate, $endDate)
            ->with(['order.parentPl', 'child'])
            ->get();

        $filterGuardian = $request->query('guardian');

        if ($filterGuardian) {
            $items = $items->filter(function ($item) use ($filterGuardian) {
                return $item->order?->parentPl?->d_code === $filterGuardian
                    || $item->order?->parentPl?->d_name === $filterGuardian;
            })->values();
        }

        $grouped = $items->groupBy(function ($item) {
            return $item->order?->parentPl?->d_code ?? 'UNKNOWN';
        })->map(function ($group) {
            $parent = $group->first()->order?->parentPl;
            $children = $group
                ->filter(fn($i) => $i->child)
                ->pluck('child.firstname')
                ->unique()
                ->values()
                ->implode(', ');

            return [
                'guardian_code'       => $parent?->d_code ?? 'UNKNOWN',
                'guardian'            => $parent?->d_name ?? 'UNKNOWN',
                'transaction_count'   => $group->count(),
                'total_items'         => $group->count(),
                'total_sales'         => $group->sum('subtotal'),
                'total_duration'      => $group->sum('durationsubtotal'),
                'total_socks'         => $group->sum('socksqty'),
                'children'            => $children ?: 'N/A',
            ];
        })->values();

        return ['data' => $grouped, 'totals' => $this->buildTotals($items)];
    }

    /**
     * mR003 – Group by HOUR of ckin (00–23). Zero-padded hours.
     */
    private function hourSalesReport($startDate, $endDate): array
    {
        $items = OrderItems::query()
            ->whereBetween('ckin', [$startDate, $endDate->endOfDay()])
            ->whereNotNull('subtotal')
            ->whereNotNull('ckin')
            ->with(['order.parentPl'])
            ->get();

        $hourLabels = collect(range(0, 23))->mapWithKeys(function ($hour) {
            return [$hour => str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00 - ' . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':59'];
        });

        $grouped = $items->groupBy(function ($item) {
            return Carbon::parse($item->ckin)->format('H');
        });

        $data = $hourLabels->map(function ($label, $hour) use ($grouped) {
            $hourItems = $grouped->get($hour, collect());
            return [
                'hour'              => $label,
                'transaction_count' => $hourItems->count(),
                'total_items'       => $hourItems->count(),
                'total_sales'       => $hourItems->sum('subtotal'),
                'total_duration'    => $hourItems->sum('durationsubtotal'),
                'total_socks'       => $hourItems->sum('socksqty'),
            ];
        })->values();

        return ['data' => $data, 'totals' => $this->buildTotals($items)];
    }

    /**
     * mR004 – Group by duration_prices item (durations_id).
     */
    private function itemSalesReport($startDate, $endDate): array
    {
        $items = $this->baseQuery($startDate, $endDate)
            ->whereHas('durationhoursprices')
            ->with(['durationhoursprices'])
            ->get();

        $grouped = $items->groupBy(function ($item) {
            return $item->durations_id;
        })->map(function ($group) {
            $price = $group->first()->durationhoursprices;
            return [
                'item_id'           => $price->id ?? null,
                'item_name'         => $price->label ?? 'UNKNOWN',
                'unit_price'        => $price->price ?? 0,
                'unit_count'        => $group->count(),
                'total_quantity'    => $group->sum('socksqty') + $group->count(),
                'total_sales'       => $group->sum('subtotal'),
                'total_duration'    => $group->sum('durationsubtotal'),
                'total_socks'       => $group->sum('socksqty'),
            ];
        })->values();

        return ['data' => $grouped, 'totals' => $this->buildTotals($items)];
    }
}
```

---

## 3. ReportsController

**File:** `app/Http/Controllers/ReportsController.php`

Replace the existing file:

```php
<?php

namespace App\Http\Controllers;

use App\Enums\MimoReport;
use App\Http\Requests\ReportRequest;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    private string $page = 'pages.admin-panel.reports';

    /**
     * Display report page with filters and data table.
     * Route: GET /admin-panel/reports/{mimo_report}
     */
    public function index(ReportRequest $request, ReportService $reportService, $mimo_report)
    {
        $reportType = MimoReport::tryFrom($mimo_report);

        if (!$reportType) {
            abort(404, 'Invalid report type');
        }

        $report = $reportService->getReport($request, $mimo_report);

        // Extract filter values for the view filter bar
        $filters = [
            'start_date' => $request->query('start_date', now()->format('Y-m-d')),
            'end_date'   => $request->query('end_date', now()->format('Y-m-d')),
            'guardian'   => $request->query('guardian', ''),
        ];

        return view($this->page, [
            'report'     => $report,
            'reportType' => $reportType,
            'filters'    => $filters,
        ]);
    }

    /**
     * Export report as PDF or CSV.
     * Route: GET /admin-panel/reports/{mimo_report}/export/{format}
     */
    public function export(ReportRequest $request, ReportService $reportService, $mimo_report, $format)
    {
        $reportType = MimoReport::tryFrom($mimo_report);
        if (!$reportType) {
            abort(404, 'Invalid report type');
        }

        $report = $reportService->generateReport($request, $mimo_report);

        if ($format === 'pdf') {
            return $this->exportPdf($report, $reportType);
        }

        if ($format === 'csv') {
            return $this->exportCsv($report, $reportType);
        }

        abort(415, 'Unsupported export format. Use pdf or csv.');
    }

    private function exportPdf(array $report, MimoReport $reportType)
    {
        $pdf = app('dompdf.wrapper');
        $html = view('exports.report-pdf', compact('report', 'reportType'));
        $pdf->loadHTML($html->render());

        $filename = strtolower(str_replace(' ', '_', $reportType->label()))
            . '_' . now()->format('Ymd')
            . '.pdf';

        return $pdf->download($filename);
    }

    private function exportCsv(array $report, MimoReport $reportType)
    {
        $filename = strtolower(str_replace([' ', '&', '/'], ['_', 'and', '_'], $reportType->label()))
            . '_' . now()->format('Ymd')
            . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($report) {
            $handle = fopen('php://output', 'w');

            // Add BOM for UTF-8 Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Report: ' . $report['report_type']]);
            fputcsv($handle, ['Date Range: ' . $report['start_date'] . ' to ' . $report['end_date']]);
            fputcsv($handle, ['Generated: ' . $report['generated_at']]);
            fputcsv($handle, []);

            $data = $report['data'];
            if (!empty($data)) {
                $headers = array_keys((array) $data[0]);
                fputcsv($handle, $headers);
                foreach ($data as $row) {
                    fputcsv($handle, array_values((array) $row));
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
```

---

## 4. Routes

**File:** `routes/admin-panel.php`

Inside the `Route::prefix('admin-panel')->middleware('auth')->group(...)` block, add the report export routes:

```php
// Reports Routes
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/{mimo_report}', [ReportsController::class, 'index'])->name('index');
    Route::get('/{mimo_report}/export/{format}', [ReportsController::class, 'export'])->name('export');
});
```

The full routes file context inside the auth group:

```php
Route::prefix('admin-panel')->middleware('auth')->group(function () {
    Route::get('/dashboard', [MimoAdminController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

    // ... existing routes ...

    // Reports Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/{mimo_report}', [ReportsController::class, 'index'])->name('index');
        Route::get('/{mimo_report}/export/{format}', [ReportsController::class, 'export'])->name('export');
    });
});
```

---

## 5. Main Report View

**File:** `resources/views/pages/admin-panel/reports.blade.php`

Replace the current placeholder:

```blade
<x-app-layout>
    <div class="flex-wrap gap-2">
        <div class="p-6">
            <x-slot name="header">
                <h2 class="font-semibold text-xl text-gray-50 leading-tight">
                    {{ $reportType->label() }}
                </h2>
            </x-slot>
        </div>

        <div class="p-6">
            <div class="bg-[var(--color-primary-dark)] rounded-lg shadow-md p-6 mb-6">

                <form method="GET" action="{{ route('reports.index', $reportType->value) }}" class="flex flex-wrap items-end gap-4">
                    <input type="hidden" name="mimo_report" value="{{ $reportType->value }}">

                    <div>
                        <label class="block text-sm font-medium text-gray-100 mb-1">Start Date</label>
                        <input type="date" name="start_date" value="{{ $filters['start_date'] }}"
                            class="rounded-md border-gray-600 bg-[var(--color-primary-mid-dark)] text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-100 mb-1">End Date</label>
                        <input type="date" name="end_date" value="{{ $filters['end_date'] }}"
                            class="rounded-md border-gray-600 bg-[var(--color-primary-mid-dark)] text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    @if($reportType->value === 'mR002')
                    <div>
                        <label class="block text-sm font-medium text-gray-100 mb-1">Guardian Filter (optional)</label>
                        <input type="text" name="guardian" value="{{ $filters['guardian'] }}" placeholder="Guardian name or code"
                            class="rounded-md border-gray-600 bg-[var(--color-primary-mid-dark)] text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-64">
                    </div>
                    @endif

                    <button type="submit"
                        class="rounded-md bg-[var(--color-accent)] px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm hover:bg-[var(--color-accent-mid-dark)] transition-all">
                        Apply Filter
                    </button>
                </form>
            </div>

            <div class="mb-6 flex flex-wrap gap-3">
                <a href="{{ route('reports.export', [$reportType->value, 'pdf']) }}"
                   class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 transition-all">
                    <i class="fa-solid fa-file-pdf mr-2"></i>Export PDF
                </a>
                <a href="{{ route('reports.export', [$reportType->value, 'csv']) }}"
                   class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700 transition-all">
                    <i class="fa-solid fa-file-csv mr-2"></i>Export CSV
                </a>
                <a href="{{ route('dashboard') }}"
                   class="rounded-md bg-[var(--color-accent-mid-dark)] px-4 py-2 text-sm font-semibold text-gray-800 shadow-sm hover:bg-[var(--color-accent)] transition-all">
                    Close
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-[var(--color-primary-mid-dark)]">
                        <tr>
                            @php
                                $data = $report['data']->first();
                                if ($data) {
                                    foreach ($data as $key => $value) {
                                        $header = str_replace('_', ' ', ucfirst($key));
                                        echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-200 uppercase tracking-wider">' . $header . '</th>';
                                    }
                                }
                            @endphp
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700 bg-[var(--color-primary-dark)]">
                        @forelse($report['data'] as $row)
                            <tr class="hover:bg-[var(--color-primary-mid-dark)] transition-colors">
                                @foreach($row as $key => $value)
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-100">
                                        @if(is_iterable($value))
                                            {{ collect($value)->implode(', ') }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="100%" class="px-6 py-8 text-center text-sm text-gray-400">
                                    No data found for the selected date range.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-[var(--color-primary-mid-dark)] font-semibold">
                        <tr>
                            @php
                                $totals = $report['totals'];
                                echo '<td class="px-6 py-3 text-sm text-gray-200" colspan="2">TOTALS</td>';
                                echo '<td class="px-6 py-3 text-sm text-gray-200">' . $totals['total_transactions'] . '</td>';
                                echo '<td class="px-6 py-3 text-sm text-gray-200">' . number_format($totals['total_sales'], 2) . '</td>';
                                echo '<td class="px-6 py-3 text-sm text-gray-200">' . number_format($totals['total_duration'], 2) . '</td>';
                                echo '<td class="px-6 py-3 text-sm text-gray-200">' . $totals['total_socks'] . '</td>';
                            @endphp
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
```

### How the view behaves

1. **Dashboard button click** → User lands on `/admin-panel/reports/mR001` (or mR002–004). No query params in the URL.
2. **`ReportRequest::prepareForValidation()`** sees no `start_date` / `end_date` in the request and injects **today's date** for both.
3. **`ReportService`** queries using today's range and returns data immediately. The table renders with real numbers.
4. **Filter form** sits visibly above the table. User changes dates and clicks **Apply Filter** → the form does a `GET` back to the same report URL with `?start_date=2026-05-01&end_date=2026-05-31`.
5. The cycle repeats — new data loads. Changing the date inputs without clicking **Apply Filter** does **not** reload the table.
                <a href="{{ route('reports.export', [$reportType->value, 'pdf']) }}"
                   class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 transition-all">
                    <i class="fa-solid fa-file-pdf mr-2"></i>Export PDF
                </a>
                <a href="{{ route('reports.export', [$reportType->value, 'csv']) }}"
                   class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700 transition-all">
                    <i class="fa-solid fa-file-csv mr-2"></i>Export CSV
                </a>
                <a href="{{ route('dashboard') }}"
                   class="rounded-md bg-[var(--color-accent-mid-dark)] px-4 py-2 text-sm font-semibold text-gray-800 shadow-sm hover:bg-[var(--color-accent)] transition-all">
                    Close
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-[var(--color-primary-mid-dark)]">
                        <tr>
                            @php
                                $data = $report['data']->first();
                                if ($data) {
                                    foreach ($data as $key => $value) {
                                        $header = str_replace('_', ' ', ucfirst($key));
                                        echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-200 uppercase tracking-wider">' . $header . '</th>';
                                    }
                                }
                            @endphp
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700 bg-[var(--color-primary-dark)]">
                        @forelse($report['data'] as $row)
                            <tr class="hover:bg-[var(--color-primary-mid-dark)] transition-colors">
                                @foreach($row as $key => $value)
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-100">
                                        @if(is_iterable($value))
                                            {{ collect($value)->implode(', ') }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="100%" class="px-6 py-8 text-center text-sm text-gray-400">
                                    No data found for the selected date range.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-[var(--color-primary-mid-dark)] font-semibold">
                        <tr>
                            @php
                                $totals = $report['totals'];
                                echo '<td class="px-6 py-3 text-sm text-gray-200" colspan="2">TOTALS</td>';
                                echo '<td class="px-6 py-3 text-sm text-gray-200">' . $totals['total_transactions'] . '</td>';
                                echo '<td class="px-6 py-3 text-sm text-gray-200">' . number_format($totals['total_sales'], 2) . '</td>';
                                echo '<td class="px-6 py-3 text-sm text-gray-200">' . number_format($totals['total_duration'], 2) . '</td>';
                                echo '<td class="px-6 py-3 text-sm text-gray-200">' . $totals['total_socks'] . '</td>';
                            @endphp
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
```

---

## 6. PDF Export View

**File:** `resources/views/exports/report-pdf.blade.php`

Create the directory and file:

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $reportType->label() }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #e5e7eb; padding: 6px; text-align: left; border: 1px solid #ccc; }
        td { padding: 6px; border: 1px solid #ccc; }
        .totals { background: #f3f4f6; font-weight: bold; }
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
                @foreach(array_keys((array) $report['data'][0]) as $header)
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
```

---

## 7. Testing Checklist

After implementing all files above, verify each report type:

- [ ] **Outlet Sales (mR001)** – Groups correctly by outlet, sums match
- [ ] **Cashier (mR002)** – Groups by guardian, optional filter works
- [ ] **Hour Sales (mR003)** – Shows 24 rows (00–23), even with zero data
- [ ] **Item Sales (mR004)** – Groups by DurationPrice label, joins correctly
- [ ] **Date filter** – Changing start/end dates updates all 4 reports
- [ ] **PDF Export** – Downloads file with correct data
- [ ] **CSV Export** – Opens correctly in Excel with UTF-8 characters
- [ ] **Invalid report type** – Route returns 404, not a server error
- [ ] **Invalid date format** – FormRequest validation catches it
- [ ] **No data found** – "No data" message displays cleanly

---

## 8. Key Imports Summary

Every new file imports these:

```php
use App\Enums\MimoReport;
use App\Models\OrderItems;
use Carbon\Carbon;
use Illuminate\Http\Request;
```

If you copy code from this document and get "class not found" errors, verify the model and enum names match your actual files:
- `app/Enums/MimoReport.php`
- `app/Models/OrderItems.php`
- `app/Models/Orders.php`

---

## 9. Data Source Quick Reference

When queries return unexpected results, check these relationships:

| Lookup | Eloquent Path | DB FK |
|--------|--------------|-------|
| Outlet name | `$item->order->parentPl->d_name` | `ordhdr.d_code → m06.d_code` |
| Guardian name | same as above | same as above |
| Item label | `$item->durationhoursprices->label` | `ordlne_ph.durations_id → duration_prices.id` |
| Child name | `$item->child->firstname` | `ordlne_ph.d_code_child → m06child.d_code_c` |

If a relationship returns null, the FK value in the database does not match the referenced table row.
