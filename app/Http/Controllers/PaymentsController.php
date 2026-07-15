<?php

namespace App\Http\Controllers;

use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\ItemsPrices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentsController extends Controller
{
    public function index(Request $request)
    {
        $request->merge([
            'start_date' => $request->input('start_date', now()->format('Y-m-d')),
            'end_date'   => $request->input('end_date', now()->format('Y-m-d')),
        ]);

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = OrderItems::query()
            ->whereNotNull('ckin')
            ->whereNotNull('ckout')
            ->where('is_paid', false)
            ->when($request->filled(['start_date', 'end_date']),
                function ($q) use ($startDate, $endDate) {
                    $q->whereDate('created_at', '>=', $startDate)
                      ->whereDate('created_at', '<=', $endDate);
                }
            )
            ->when($request->filled('search'),
                function ($q) use ($request) {
                    $search = $request->input('search');
                    $q->where(function ($qu) use ($search) {
                        $qu->where('ord_code_ph', 'like', "%{$search}%")
                           ->orWhereHas('child', function ($childSearch) use ($search) {
                                $childSearch->where('firstname', 'like', "%{$search}%")
                                    ->orWhere('lastname', 'like', "%{$search}%");
                           });
                    });
                }
            );

        $orderItems = $query->with(['child', 'order.parentPl'])
            ->orderBy('ckout', 'asc')
            ->paginate(20)
            ->through(function ($item) {
                $item->amount_due = $this->amountDue($item);

                return $item;
            })
            ->withQueryString();

        return view('pages.playhouse-payments', compact('orderItems'));
    }

    public function show($ord_code_ph)
    {
        $order = Orders::where('ord_code_ph', $ord_code_ph)
            ->with(['parentPl', 'orderItems.child'])
            ->firstOrFail();

        $order->orderItems->each(function ($item) {
            $item->status = $this->resolveStatus($item);
            $item->amount_due = $this->amountDue($item);
            $item->overtime_breakdown = $this->overtimeBreakdown($item);
        });

        return view('pages.playhouse-payment-show', compact('order'));
    }

    public function details($id)
    {
        $orderItem = OrderItems::with(['child', 'order.parentPl'])->findOrFail($id);

        return response()->json([
            'id' => $orderItem->id,
            'ord_code_ph' => $orderItem->ord_code_ph,
            'child' => $orderItem->child ? [
                'd_code_c' => $orderItem->child->d_code_c,
                'firstname' => $orderItem->child->firstname,
                'lastname' => $orderItem->child->lastname,
            ] : null,
            'parent_name' => $orderItem->order?->parentPl?->d_name ?? $orderItem->guardian,
            'guardian' => $orderItem->guardian ?? $orderItem->order?->parentPl?->d_name,
            'ckin' => $orderItem->ckin,
            'durationhours' => $orderItem->durationhours,
            'durationsubtotal' => $orderItem->durationsubtotal,
            'socksqty' => $orderItem->socksqty,
            'socksprice' => $orderItem->socksprice,
            'others_amnt' => $orderItem->others_amnt,
            'disc_amnt' => $orderItem->disc_amnt,
            'lne_xtra_chrg' => $orderItem->lne_xtra_chrg,
            'amount_due' => $this->amountDue($orderItem),
            'checked_out' => !empty($orderItem->ckout),
            'is_paid' => $orderItem->is_paid,
            'cash_tendered' => $orderItem->cash_tendered,
            'change_amnt' => $orderItem->change_amnt,
            'paid_at' => $orderItem->paid_at,
            'overtime_breakdown' => $this->overtimeBreakdown($orderItem),
        ]);
    }

    public function pay(Request $request, $id)
    {
        $data = $request->validate([
            'cash_tendered' => 'required|numeric|min:0',
        ]);

        $orderItem = OrderItems::findOrFail($id);

        if (empty($orderItem->ckout)) {
            return response()->json(['success' => false, 'message' => 'This child has not been checked out yet.'], 422);
        }

        if ($orderItem->is_paid) {
            return response()->json(['success' => false, 'message' => 'This order item is already paid.'], 422);
        }

        $amountDue = $this->amountDue($orderItem);

        if ($data['cash_tendered'] < $amountDue) {
            return response()->json([
                'success' => false,
                'message' => 'Cash tendered is less than the amount due (₱' . number_format($amountDue, 2) . ').',
            ], 422);
        }

        $orderItem->cash_tendered = $data['cash_tendered'];
        $orderItem->change_amnt = $data['cash_tendered'] - $amountDue;
        $orderItem->is_paid = true;
        $orderItem->paid_at = Carbon::now();
        $orderItem->checked_out = true;
        $orderItem->save();

        return response()->json([
            'success' => true,
            'orderItem' => $orderItem->fresh(),
        ]);
    }

    public function cancelCheckout($id)
    {
        try {
            DB::beginTransaction();

            $orderItem = OrderItems::with('order')->findOrFail($id);

            if ($orderItem->is_paid) {
                return response()->json(['success' => false, 'message' => 'Cannot cancel an already-paid checkout.'], 422);
            }

            if (empty($orderItem->ckout)) {
                return response()->json(['success' => false, 'message' => 'This child has not been checked out.'], 422);
            }

            $order = $orderItem->order;
            $order->xtra_chrg_amnt -= $orderItem->lne_xtra_chrg;
            $order->total_amnt -= $orderItem->lne_xtra_chrg;
            $order->save();

            $orderItem->lne_xtra_chrg = 0;
            $orderItem->checked_out = false;
            $orderItem->ckout = null;
            $orderItem->save();

            DB::commit();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function amountDue(OrderItems $item): float
    {
        return (float) $item->subtotal + (float) $item->lne_xtra_chrg;
    }

    /**
     * Reconstructs the overtime math behind the persisted lne_xtra_chrg,
     * for display only (mirrors the calculation already done in PlayHouseController::checkOut).
     */
    private function overtimeBreakdown(OrderItems $item): ?array
    {
        if ((float) $item->lne_xtra_chrg <= 0 || !$item->ckin || !$item->ckout) {
            return null;
        }

        $items = ItemsPrices::pluck('price', 'item');
        $minutesPerCharge = (float) ($items['minutes_per_charge'] ?? 0);
        $chargeOfMinutes = (float) ($items['charge_of_minutes'] ?? 0);

        $checkIn = Carbon::parse($item->ckin);
        $checkOut = Carbon::parse($item->ckout);
        $actualMinutes = min($checkIn->diffInMinutes($checkOut), 5 * 60);
        $paidMinutes = $item->durationhours * 60;
        $extraMinutes = max(0, $actualMinutes - $paidMinutes);
        $chargeUnits = $minutesPerCharge > 0 ? ceil($extraMinutes / $minutesPerCharge) : 0;

        return [
            'actual_minutes' => $actualMinutes,
            'paid_minutes' => $paidMinutes,
            'extra_minutes' => $extraMinutes,
            'charge_units' => $chargeUnits,
            'rate' => $chargeOfMinutes,
            'minutes_per_charge' => $minutesPerCharge,
        ];
    }

    private function resolveStatus(OrderItems $item): string
    {
        if (empty($item->ckin)) {
            return 'booked';
        }

        if (!empty($item->ckout)) {
            return $item->is_paid ? 'paid' : 'done';
        }

        return 'normal';
    }
}
