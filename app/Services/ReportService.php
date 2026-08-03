<?php

namespace App\Services;

use App\Enums\MimoReport;
use App\Models\OrderItems;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getReport(Request $request, string $mimo_report): array
    {
        $reportType = $this->resolveReportType($mimo_report);
        $startDate = $this->resolveDate($request, 'start_date');
        $endDate = $this->resolveDate($request, 'end_date');

        $result = match ($reportType) {
            MimoReport::OUTLET_SALES => $this->outletSalesReport($startDate, $endDate),
            MimoReport::TRANSACTION => $this->transactionReport($startDate, $endDate, $request),
            MimoReport::HOUR_SALES => $this->hourSalesReport($startDate, $endDate),
            MimoReport::ITEM_SALES => $this->itemSalesReport($startDate, $endDate),
        };

        $perPage = (int) $request->query('per_page', 25);
        $page = (int) $request->query('page', 1);

        $paginatedData = new LengthAwarePaginator(
            $result['data']->forPage($page, $perPage)->values(),
            $result['data']->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return [
            'data' => $paginatedData,
            'totals' => $result['totals'],
            'type' => $reportType,
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d'),
        ];
    }

    public function generateReport(Request $request, string $mimo_report): array
    {
        $reportType = $this->resolveReportType($mimo_report);
        $startDate = $this->resolveDate($request, 'start_date');
        $endDate = $this->resolveDate($request, 'end_date');

        $result = match ($reportType) {
            MimoReport::OUTLET_SALES => $this->outletSalesReport($startDate, $endDate),
            MimoReport::TRANSACTION => $this->transactionReport($startDate, $endDate, $request),
            MimoReport::HOUR_SALES => $this->hourSalesReport($startDate, $endDate),
            MimoReport::ITEM_SALES => $this->itemSalesReport($startDate, $endDate),
        };

        return [
            'data' => $result['data'],
            'totals' => $result['totals'],
            'report_type' => $reportType->label(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    private function resolveReportType(string $mimo_report): MimoReport
    {
        $report = MimoReport::tryFrom($mimo_report);
        if (! $report) {
            throw new \InvalidArgumentException("Invalid report type: {$mimo_report}");
        }

        return $report;
    }

    private function resolveDate(mixed $request, string $key): Carbon
    {
        $dateStr = $request->query($key) ?? now()->format('Y-m-d');

        return Carbon::parse($dateStr);
    }

    private function baseQuery(mixed $startDate, mixed $endDate)
    {
        return OrderItems::query()
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->whereNotNull('subtotal');
    }

    private function buildTotals(mixed $startDate, mixed $endDate, ?\Illuminate\Database\Eloquent\Builder $query = null): array
    {
        $q = $query ?: $this->baseQuery($startDate, $endDate);

        return [
            'total_transactions' => $q->count(),
            'total_sales' => $q->sum('subtotal') ?? 0,
            'total_duration' => $q->sum('durationsubtotal') ?? 0,
            'total_socks' => $q->sum('socksqty') ?? 0,
        ];
    }

    private function outletSalesReport(mixed $startDate, mixed $endDate): array
    {
        $items = $this->baseQuery($startDate, $endDate)
            ->with(['order'])
            ->get();

        $outlet = DB::table('outlet')->select('out_code', 'out_desc')->first();

        $summary = [
            'outlet_code' => $outlet->out_code,
            'outlet' => $outlet->out_desc,
            'transaction_count' => number_format($items->pluck('ord_code_ph')->unique()->count()),
            'total_items' => number_format($items->count()),
            'total_sales' => number_format($items->sum('subtotal'), 2),
            'total_duration' => number_format($items->sum('durationsubtotal'), 2),
            'total_socks' => number_format($items->sum('socksqty')),
        ];

        return ['data' => collect([$summary]), 'totals' => $this->buildTotals($startDate, $endDate)];
    }

    private function transactionReport(mixed $startDate, mixed $endDate, mixed $request): array
    {
        $query = $this->baseQuery($startDate, $endDate)
            ->with(['order.parentPl', 'child']);

        $filterGuardian = $request->query('guardian');

        if ($filterGuardian) {
            $query->whereHas('order.parentPl', function ($q) use ($filterGuardian) {
                $q->where(function ($q2) use ($filterGuardian) {
                    $q2->where('d_code', $filterGuardian)
                        ->orWhere('d_name', $filterGuardian);
                });
            });
        }

        $items = $query->get();

        $grouped = $items->groupBy(function ($item) {
            return $item->order?->parentPl?->d_code ?? 'UNKNOWN';
        })->map(function ($group) {
            $parent = $group->first()->order?->parentPl;
            $children = $group
                ->filter(fn ($i) => $i->child)
                ->pluck('child.firstname')
                ->unique()
                ->values()
                ->implode(' | ');

            return [
                'guardian_code' => $parent?->d_code ?? 'UNKNOWN',
                'guardian' => $parent?->d_name ?? 'UNKNOWN',
                'transaction_count' => number_format($group->count()),
                'total_items' => number_format($group->count()),
                'total_sales' => number_format($group->sum('subtotal'), 2),
                'total_duration' => number_format($group->sum('durationsubtotal'), 2),
                'total_socks' => number_format($group->sum('socksqty')),
                'children' => $children ?: 'N/A',
            ];
        })->values();

        return ['data' => $grouped, 'totals' => $this->buildTotals($startDate, $endDate, $query)];
    }

    private function hourSalesReport(mixed $startDate, mixed $endDate): array
    {
        $items = OrderItems::query()
            ->whereBetween('ckin', [$startDate, $endDate->endOfDay()])
            ->whereNotNull('subtotal')
            ->whereNotNull('ckin')
            ->with(['order.parentPl'])
            ->get();

        $hourLabels = collect(range(0, 23))->mapWithKeys(function ($hour) {
            return [$hour => str_pad($hour, 2, '0', STR_PAD_LEFT).':00 - '.str_pad($hour, 2, '0', STR_PAD_LEFT).':59'];
        });

        $grouped = $items->groupBy(function ($item) {
            return Carbon::parse($item->ckin)->format('H');
        });

        $data = $hourLabels->map(function ($label, $hour) use ($grouped) {
            $hourItems = $grouped->get($hour, collect());

            return [
                'hour' => $label,
                'transaction_count' => number_format($hourItems->count()),
                'total_items' => number_format($hourItems->count()),
                'total_sales' => number_format($hourItems->sum('subtotal'), 2),
                'total_duration' => number_format($hourItems->sum('durationsubtotal'), 2),
                'total_socks' => number_format($hourItems->sum('socksqty')),
            ];
        })->values();

        return ['data' => $data, 'totals' => $this->buildTotals($startDate, $endDate)];
    }

    private function itemSalesReport(mixed $startDate, mixed $endDate): array
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
                'item_id' => $price->id ?? null,
                'item_name' => $price->label ?? 'UNKNOWN',
                'unit_price' => number_format($price->price ?? 0, 2),
                'unit_count' => number_format($group->count()),
                'total_quantity' => number_format($group->sum('socksqty') + $group->count()),
                'total_sales' => number_format($group->sum('subtotal'), 2),
                'total_duration' => number_format($group->sum('durationsubtotal'), 2),
                'total_socks' => number_format($group->sum('socksqty')),
            ];
        })->values();

        return ['data' => $grouped, 'totals' => $this->buildTotals($startDate, $endDate)];
    }
}
