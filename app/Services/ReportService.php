<?php

namespace App\Services;

use App\Enums\MimoReport;
use App\Models\OfficialReceipt;
use App\Models\OrderItems;
use App\Models\PaymentMode;
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
            MimoReport::CASHIER => $this->cashierReport($startDate, $endDate),
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
            MimoReport::CASHIER => $this->cashierReport($startDate, $endDate),
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

    /**
     * Sourced from orhdr (the legacy Official Receipt ledger), not orlne_pay — orhdr
     * is the only table with payment history predating this app's split-payment
     * feature (192 of 193 rows were written by the old system, before 2026-07-31),
     * and it's what `recordOfficialReceipt()` now writes to going forward once an
     * order item becomes fully paid. orhdr has two payment-method slots per row
     * (pay_code/payment, pay_code2/payment2); both are unpacked into separate lines
     * before grouping so a split payment counts each method correctly. `payment`/
     * `payment2` are stored negative (accounting convention) in both eras, so we
     * negate; `amnt_tendered` is NOT used here because legacy rows store it negative
     * while recordOfficialReceipt() writes it positive — an existing inconsistency
     * that isn't safe to build totals on without normalizing at the source.
     *
     * Caveat: a row only exists once an item is FULLY paid, so a same-day partial
     * payment (e.g. a deposit toward a balance settled later) won't appear here
     * until the day it's completed — this report is not a live cash-drawer count.
     */
    private function cashierReport(mixed $startDate, mixed $endDate): array
    {
        $receipts = OfficialReceipt::query()
            ->whereNotNull('ord_code_ph')
            ->whereBetween('trnx_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where(function ($q) {
                $q->whereNull('cancel')->orWhere('cancel', '<>', 'Y');
            })
            ->get(['pay_code', 'payment', 'pay_code2', 'payment2']);

        $lines = collect();

        foreach ($receipts as $receipt) {
            if ((float) $receipt->payment != 0) {
                $lines->push([
                    'code' => $receipt->pay_code ?: 'UNSPECIFIED',
                    'amount' => -round((float) $receipt->payment, 2),
                ]);
            }

            if (! empty($receipt->pay_code2) && (float) $receipt->payment2 != 0) {
                $lines->push([
                    'code' => $receipt->pay_code2,
                    'amount' => -round((float) $receipt->payment2, 2),
                ]);
            }
        }

        $modeLabels = DB::table('m10')->pluck('mp_desc', 'mp_code');

        $grouped = $lines->groupBy('code')->map(function ($group, $code) use ($modeLabels) {
            $label = match (true) {
                $code === PaymentMode::CHARGE_CODE => 'Charge to Account',
                $code === 'UNSPECIFIED' => 'Unspecified',
                default => $modeLabels[$code] ?? $code,
            };

            return [
                'payment_method' => $label,
                'transaction_count' => number_format($group->count()),
                'total_amount' => number_format($group->sum('amount'), 2),
            ];
        })->values();

        return [
            'data' => $grouped,
            'totals' => [
                'total_transactions' => $receipts->count(),
                'total_sales' => $lines->sum('amount'),
                'total_duration' => 0,
                'total_socks' => 0,
            ],
        ];
    }
}
