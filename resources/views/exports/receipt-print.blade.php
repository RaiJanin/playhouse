<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt - {{ $ordCodePh }}</title>
    <style>
        @page {
            size: 58mm auto;
            margin: 0;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            width: 58mm;
            margin: 0;
            padding: 2mm;
        }
        h1 {
            font-size: 14px;
            margin: 0;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #555;
            margin-bottom: 6px;
        }
        .meta {
            margin-bottom: 8px;
        }
        .meta div {
            display: flex;
            justify-content: space-between;
            gap: 6px;
        }
        .divider {
            border-top: 1px dashed #888;
            margin: 6px 0;
        }
        .item {
            margin-bottom: 6px;
        }
        .item .name {
            font-weight: bold;
        }
        .row {
            display: flex;
            justify-content: space-between;
            gap: 6px;
        }
        .row.muted {
            color: #555;
        }
        .totals .row {
            padding: 2px 0;
        }
        .grand-total {
            font-weight: bold;
            font-size: 13px;
            border-top: 1px solid #333;
            padding-top: 4px;
            margin-top: 4px;
        }
        .payments {
            margin-top: 6px;
        }
        .payments .row {
            padding: 1px 0;
        }
        .footer {
            text-align: center;
            margin-top: 10px;
            color: #555;
        }
        .none {
            color: #999;
            font-style: italic;
            text-align: center;
        }

        @media print {
            body {
                padding: 0 2mm;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 10px;">
        <button onclick="window.print()" style="padding: 8px 16px; font-size: 12px; cursor: pointer;">
            Print
        </button>
        <button onclick="window.close()" style="padding: 8px 16px; font-size: 12px; cursor: pointer;">
            Close
        </button>
    </div>

    <h1>Mimo Play Cafe</h1>
    <div class="subtitle">Official Receipt</div>

    <div class="meta">
        <div><span>Booking#</span><span>{{ $ordCodePh }}</span></div>
        @if($receiptNo)
            <div><span>OR#</span><span>{{ $receiptNo }}</span></div>
        @endif
        <div><span>Customer</span><span>{{ $customer ?: 'N/A' }}</span></div>
        <div><span>Date</span><span>{{ $paidAt ? \Carbon\Carbon::parse($paidAt)->format('M d, Y h:i A') : now()->format('M d, Y h:i A') }}</span></div>
        <div><span>Cashier</span><span>{{ $cashier }}</span></div>
    </div>

    <div class="divider"></div>

    @forelse($items as $item)
        <div class="item">
            <div class="name">{{ $item['name'] }}</div>
            <div class="row"><span>Playtime ({{ $item['duration_label'] }})</span><span>&#8369;{{ number_format($item['duration_amount'], 2) }}</span></div>
            @if($item['socks_qty'] > 0)
                <div class="row"><span>Socks x{{ $item['socks_qty'] }}</span><span>&#8369;{{ number_format($item['socks_amount'], 2) }}</span></div>
            @endif
            @if($item['others_amount'] > 0)
                <div class="row"><span>Others</span><span>&#8369;{{ number_format($item['others_amount'], 2) }}</span></div>
            @endif
            @if($item['extra_amount'] > 0)
                <div class="row"><span>Overtime</span><span>&#8369;{{ number_format($item['extra_amount'], 2) }}</span></div>
            @endif
            @if($item['discount_amount'] > 0)
                <div class="row muted"><span>Discount</span><span>-&#8369;{{ number_format($item['discount_amount'], 2) }}</span></div>
            @endif
            <div class="row"><span><strong>Subtotal</strong></span><span><strong>&#8369;{{ number_format($item['subtotal'], 2) }}</strong></span></div>
        </div>
    @empty
        <p class="none">No items</p>
    @endforelse

    <div class="divider"></div>

    <div class="totals">
        <div class="row grand-total"><span>Total Due</span><span>&#8369;{{ number_format($totalDue, 2) }}</span></div>
    </div>

    <div class="divider"></div>

    <div class="payments">
        @forelse($payments as $payment)
            <div class="row">
                <span>{{ $paymentModeLabels[$payment->payment_method] ?? $payment->payment_method }}</span>
                <span>&#8369;{{ number_format($payment->amount, 2) }}</span>
            </div>
            @if($payment->reference)
                <div class="row muted"><span>Ref#</span><span>{{ $payment->reference }}</span></div>
            @endif
            @if($payment->chargeAccount)
                <div class="row muted"><span>Account</span><span>{{ $payment->chargeAccount->name }}</span></div>
            @endif
        @empty
            <p class="none">No payments recorded</p>
        @endforelse

        <div class="row" style="margin-top: 4px;"><span><strong>Total Paid</strong></span><span><strong>&#8369;{{ number_format($totalPaid, 2) }}</strong></span></div>

        @if($totalTendered > 0)
            <div class="row"><span>Cash Tendered</span><span>&#8369;{{ number_format($totalTendered, 2) }}</span></div>
            <div class="row"><span>Change</span><span>&#8369;{{ number_format($totalChange, 2) }}</span></div>
        @endif
    </div>

    <div class="footer">
        Thank you and see you again!
    </div>

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
