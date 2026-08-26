<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddPaymentRequest;
use App\Http\Requests\CancelCheckoutRequest;
use App\Models\ChargeAccount;
use App\Models\OfficialReceipt;
use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\Outlet;
use App\Models\ItemsPrices;
use App\Models\PaymentMode;
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

    public function chargeAccounts()
    {
        return response()->json(ChargeAccount::orderBy('name')->pluck('name'));
    }

    public function paymentModes()
    {
        $modes = PaymentMode::where('active', true)
            ->orderBy('mp_desc')
            ->get(['mp_code', 'mp_desc'])
            ->map(fn ($mode) => ['code' => $mode->mp_code, 'label' => $mode->mp_desc])
            ->values();

        $modes->push(['code' => PaymentMode::CHARGE_CODE, 'label' => 'Charge to Account']);

        return response()->json($modes);
    }

    public function details($id)
    {
        $orderItem = OrderItems::with(['child', 'order.parentPl'])->findOrFail($id);
        $payments = $orderItem->payments()
            ->with('chargeAccount:id,name')
            ->orderBy('paid_at')
            ->get(['id', 'payment_method', 'amount', 'cash_tendered', 'change_amnt', 'reference', 'remarks', 'charge_account_id', 'paid_at']);

        $amountDue = $this->amountDue($orderItem);
        $amountPaid = (float) $payments->sum('amount');

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
            'amount_due' => $amountDue,
            'amount_paid' => $amountPaid,
            'remaining_due' => max(0, round($amountDue - $amountPaid, 2)),
            'checked_out' => !empty($orderItem->ckout),
            'is_paid' => $orderItem->is_paid,
            'cash_tendered' => $orderItem->cash_tendered,
            'change_amnt' => $orderItem->change_amnt,
            'paid_at' => $orderItem->paid_at,
            'payments' => $payments,
            'overtime_breakdown' => $this->overtimeBreakdown($orderItem),
        ]);
    }

    public function pay(AddPaymentRequest $request, $id)
    {
        $data = $request->validated();

        $orderItem = OrderItems::findOrFail($id);

        if ($orderItem->is_paid) {
            return response()->json(['success' => false, 'message' => 'This order item is already fully paid.'], 422);
        }

        $amountDue = $this->amountDue($orderItem);
        $alreadyPaid = (float) $orderItem->payments()->sum('amount');
        $remaining = round($amountDue - $alreadyPaid, 2);

        if ($remaining <= 0) {
            return response()->json(['success' => false, 'message' => 'This order item is already fully paid.'], 422);
        }

        $chargeAccountId = null;

        if ($data['payment_method'] === PaymentMode::CASH_CODE) {
            $cashTendered = (float) $data['cash_tendered'];
            $amountApplied = min($cashTendered, $remaining);
            $changeAmnt = round($cashTendered - $amountApplied, 2);
        } else {
            $amountApplied = (float) $data['amount'];

            if ($amountApplied > $remaining + 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'Amount exceeds the remaining balance (₱' . number_format($remaining, 2) . ').',
                ], 422);
            }

            $cashTendered = null;
            $changeAmnt = null;

            if ($data['payment_method'] === PaymentMode::CHARGE_CODE) {
                $chargeAccount = ChargeAccount::firstOrCreate([
                    'name' => trim($data['charge_account_name']),
                ]);
                $chargeAccountId = $chargeAccount->id;
            }
        }

        $orderItem->payments()->create([
            'ord_code_ph' => $orderItem->ord_code_ph,
            'payment_method' => $data['payment_method'],
            'amount' => $amountApplied,
            'cash_tendered' => $cashTendered,
            'change_amnt' => $changeAmnt,
            'reference' => $data['reference'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'charge_account_id' => $chargeAccountId,
            'paid_at' => Carbon::now(),
        ]);

        $totalPaid = round($alreadyPaid + $amountApplied, 2);
        $orderItem->cash_tendered = (float) $orderItem->payments()->sum('cash_tendered');
        $orderItem->change_amnt = (float) $orderItem->payments()->sum('change_amnt');

        if ($totalPaid >= $amountDue - 0.01) {
            $orderItem->is_paid = true;
            $orderItem->paid_at = Carbon::now();
        }

        $orderItem->save();
        $this->syncOrderPaymentTotals($orderItem->ord_code_ph);

        if ($orderItem->is_paid) {
            $this->recordOfficialReceipt($orderItem);
        }

        return response()->json([
            'success' => true,
            'orderItem' => $orderItem->fresh('payments'),
            'remaining_due' => max(0, round($amountDue - $totalPaid, 2)),
        ]);
    }

    /**
     * Applies one payment mode/amount across every checked-out, unpaid item under
     * a booking in a single transaction — oldest item first — splitting the tendered
     * amount until it's exhausted, exactly like calling pay() per item but atomic.
     */
    public function payAll(AddPaymentRequest $request, $ordCodePh)
    {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            $order = Orders::where('ord_code_ph', $ordCodePh)->lockForUpdate()->first();

            if (!$order) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
            }

            $unpaidItems = OrderItems::where('ord_code_ph', $ordCodePh)
                ->where('is_paid', false)
                ->orderBy('id')
                ->get();

            if ($unpaidItems->isEmpty()) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'No unpaid, checked-out children to pay.'], 422);
            }

            $remainingDueByItem = [];
            $totalDue = 0.0;

            foreach ($unpaidItems as $item) {
                $due = round($this->amountDue($item) - (float) $item->payments()->sum('amount'), 2);
                $remainingDueByItem[$item->id] = $due;
                $totalDue = round($totalDue + $due, 2);
            }

            $isCash = $data['payment_method'] === PaymentMode::CASH_CODE;
            $tenderedAmount = $isCash ? (float) $data['cash_tendered'] : (float) $data['amount'];

            if (!$isCash && $tenderedAmount > $totalDue + 0.01) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Amount exceeds the total outstanding balance (₱' . number_format($totalDue, 2) . ').',
                ], 422);
            }

            $chargeAccountId = null;

            if ($data['payment_method'] === PaymentMode::CHARGE_CODE) {
                $chargeAccount = ChargeAccount::firstOrCreate(['name' => trim($data['charge_account_name'])]);
                $chargeAccountId = $chargeAccount->id;
            }

            $amountToApply = min($tenderedAmount, $totalDue);
            $remainingToApply = $amountToApply;
            $paidItemIds = [];
            $partiallyPaidCount = 0;
            $lastPaidItem = null;

            foreach ($unpaidItems as $item) {
                if ($remainingToApply <= 0.001) {
                    break;
                }

                $itemDue = $remainingDueByItem[$item->id];

                if ($itemDue <= 0) {
                    continue;
                }

                $amountApplied = round(min($itemDue, $remainingToApply), 2);
                $remainingToApply = round($remainingToApply - $amountApplied, 2);
                $lastPaidItem = $item;

                $item->payments()->create([
                    'ord_code_ph' => $item->ord_code_ph,
                    'payment_method' => $data['payment_method'],
                    'amount' => $amountApplied,
                    'cash_tendered' => $isCash ? $amountApplied : null,
                    'change_amnt' => null,
                    'reference' => $data['reference'] ?? null,
                    'remarks' => $data['remarks'] ?? null,
                    'charge_account_id' => $chargeAccountId,
                    'paid_at' => Carbon::now(),
                ]);

                $item->cash_tendered = (float) $item->payments()->sum('cash_tendered');
                $item->change_amnt = (float) $item->payments()->sum('change_amnt');

                $totalPaidForItem = round((float) $item->payments()->sum('amount'), 2);

                if ($totalPaidForItem >= $this->amountDue($item) - 0.01) {
                    $item->is_paid = true;
                    $item->paid_at = Carbon::now();
                    $paidItemIds[] = $item->id;
                } else {
                    $partiallyPaidCount++;
                }

                $item->save();
            }

            // Any leftover cash tendered beyond the total due is change — folded
            // onto the last item's payment so the item-level cash/change sums stay
            // internally consistent (see PaymentsController::pay() for the pattern).
            $changeAmnt = $isCash ? round($tenderedAmount - $amountToApply, 2) : 0.0;

            if ($isCash && $changeAmnt > 0 && $lastPaidItem) {
                $lastPayment = $lastPaidItem->payments()->latest('id')->first();
                $lastPayment->change_amnt = $changeAmnt;
                $lastPayment->cash_tendered += $changeAmnt;
                $lastPayment->save();

                $lastPaidItem->cash_tendered = (float) $lastPaidItem->payments()->sum('cash_tendered');
                $lastPaidItem->change_amnt = (float) $lastPaidItem->payments()->sum('change_amnt');
                $lastPaidItem->save();
            }

            DB::commit();

            foreach ($paidItemIds as $id) {
                $this->recordOfficialReceipt(OrderItems::find($id));
            }
            $this->syncOrderPaymentTotals($ordCodePh);

            return response()->json([
                'success' => true,
                'items_paid' => count($paidItemIds),
                'items_partial' => $partiallyPaidCount,
                'total_applied' => $amountToApply,
                'change_amnt' => $changeAmnt,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function removePayment($id, $paymentId)
    {
        $orderItem = OrderItems::findOrFail($id);

        if ($orderItem->is_paid) {
            return response()->json(['success' => false, 'message' => 'Cannot remove a payment from an item that is already fully paid.'], 422);
        }

        $payment = $orderItem->payments()->findOrFail($paymentId);
        $payment->delete();

        $orderItem->cash_tendered = (float) $orderItem->payments()->sum('cash_tendered');
        $orderItem->change_amnt = (float) $orderItem->payments()->sum('change_amnt');
        $orderItem->save();
        $this->syncOrderPaymentTotals($orderItem->ord_code_ph);

        return response()->json(['success' => true]);
    }

    public function cancelCheckout(CancelCheckoutRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $orderItem = OrderItems::with('order')->findOrFail($id);

            if ($orderItem->is_paid) {
                return response()->json(['success' => false, 'message' => 'Cannot cancel an already-paid checkout.'], 422);
            }

            if ($orderItem->payments()->exists()) {
                return response()->json(['success' => false, 'message' => 'Cannot cancel checkout — a payment has already been recorded for this item. Remove the payment first.'], 422);
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
     * Recomputes the order-header payment rollup (paid_amnt/is_paid/paid_at) from
     * its orlne_pay rows and order items — called after every payment add/remove so
     * ordhdr always reflects how much of the whole booking is paid so far. This is
     * purely an internal rollup now; it does NOT drive the legacy Official Receipt
     * write (see recordOfficialReceipt()) — a booking's children can be checked out
     * and paid independently, and some bookings have siblings that were never (and
     * will never be) processed through this system, so waiting on "every item paid"
     * would silently skip writing a receipt at all for otherwise-complete payments.
     */
    private function syncOrderPaymentTotals(string $ordCodePh): void
    {
        DB::transaction(function () use ($ordCodePh) {
            $order = Orders::where('ord_code_ph', $ordCodePh)->lockForUpdate()->first();

            if (!$order) {
                return;
            }

            $order->paid_amnt = (float) $order->payments()->sum('amount');

            $fullyPaid = $order->orderItems()->where('is_paid', false)->doesntExist();

            $order->is_paid = $fullyPaid;
            $order->paid_at = $fullyPaid ? ($order->paid_at ?? Carbon::now()) : null;

            $order->save();
        });
    }

    /**
     * Writes the legacy Official Receipt header (orhdr — not this app's own ordhdr)
     * for a single order item the moment IT becomes fully paid, independent of any
     * sibling items under the same booking (see class doc above for why). orhdr has
     * no per-item column and no unique constraint on ord_code_ph — several item-level
     * rows can legitimately share the same ord_code_ph — so idempotency here is keyed
     * on `reference` ("SO#-<ord_code_ph>-<item id>"), not ord_code_ph alone. orhdr only
     * has two payment-method slots per row, so when an item's payment was split across
     * 2+ methods, the chronologically-first fills slot 1 and everything else is folded
     * into slot 2.
     */
    private function recordOfficialReceipt(OrderItems $orderItem): void
    {
        $reference = 'SO#-' . $orderItem->ord_code_ph . '-' . $orderItem->id;

        if (OfficialReceipt::where('reference', $reference)->exists()) {
            return;
        }

        $order = $orderItem->order ?? Orders::where('ord_code_ph', $orderItem->ord_code_ph)->first();

        if (!$order) {
            return;
        }

        DB::transaction(function () use ($orderItem, $order, $reference) {
            $outlet = Outlet::query()->lockForUpdate()->first();

            if (!$outlet) {
                return;
            }

            $ordCode = $outlet->ord_code;
            $outlet->ord_code = str_pad((string) ((int) $ordCode + 1), strlen($ordCode), '0', STR_PAD_LEFT);
            $outlet->save();

            $groups = $orderItem->payments()
                ->selectRaw('payment_method, SUM(amount) as amount, SUM(COALESCE(cash_tendered, amount)) as tendered, MIN(paid_at) as first_paid_at')
                ->groupBy('payment_method')
                ->orderBy('first_paid_at')
                ->get();

            $slot1 = $groups->shift();
            $rest = $groups;

            $amountDue = $this->amountDue($orderItem);
            $now = Carbon::now();
            $userName = auth()->user()?->name ?? 'SYSTEM';

            OfficialReceipt::create([
                'out_code' => $outlet->out_code,
                'ord_code' => $ordCode,
                'customer' => $order->parent,
                'ord_date' => $now->toDateString(),
                'net_amnt' => $amountDue,
                'tax_amnt' => 0,
                'total_amnt' => $amountDue,
                'disc_amnt' => $orderItem->disc_amnt ?? 0,
                'user_id' => $userName,
                't_date' => $now->toDateString(),
                't_time' => $now->format('H:i'),
                'loc' => $outlet->branch,
                'user_id2' => $userName,
                't_date2' => $now->toDateString(),
                't_time2' => $now->format('H:i'),
                'trnx_date' => $now->toDateString(),
                'debt_code' => $order->d_code,
                'branch' => $outlet->branch,
                'reference' => $reference,
                'payment' => $slot1 ? -round((float) $slot1->amount, 2) : 0,
                'pay_code' => $slot1?->payment_method,
                'pending' => 'N',
                'amnt_tendered' => $slot1 ? round((float) $slot1->tendered, 2) : 0,
                'ord_amnt' => $amountDue,
                'amnt_due' => 0,
                'due_date' => $now->toDateString(),
                'ord_code_ph' => $orderItem->ord_code_ph,
                'payment2' => $rest->isNotEmpty() ? -round((float) $rest->sum('amount'), 2) : 0,
                'pay_code2' => $rest->isNotEmpty() ? $rest->first()->payment_method : null,
                'amnt_tendered2' => $rest->isNotEmpty() ? round((float) $rest->sum('tendered'), 2) : 0,
            ]);
        });
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
