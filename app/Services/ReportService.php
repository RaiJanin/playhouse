<?php

namespace App\Services;

use App\Enums\MimoReport;
use App\Models\OrderItems;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportService
{
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

    public function generateReport(Request $request, $mimo_report): array
    {
        $reportType = $this->resolveReportType($mimo_report);
        $startDate  = $this->resolveDate($request, 'start_date');
        $endDate    = $this->resolveDate($request, 'end_date');

        $result = match($reportType) {
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

    private function resolveReportType($mimo_report): MimoReport
    {
        $report = MimoReport::tryFrom($mimo_report);
        if (!$report) {
            throw new \InvalidArgumentException("Invalid report type: {$mimo_report}");
        }
        return $report;
    }

    private function resolveDate($request, string $key): Carbon
    {
        $dateStr = $request->query($key) ?? now()->format('Y-m-d');
        return Carbon::parse($dateStr);
    }

    private function baseQuery($startDate, $endDate)
    {
        return OrderItems::query()
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
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
