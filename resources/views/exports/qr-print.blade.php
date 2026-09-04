<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>QR Codes - {{ $orderItem->ord_code_ph }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 10px;
        }
        h1 {
            font-size: 14px;
            margin: 0;
            text-align: center;
        }
        .booking {
            text-align: center;
            color: #555;
            margin-bottom: 10px;
        }
        .qr-block {
            text-align: center;
            margin-bottom: 12px;
        }
        .qr-block h3 {
            font-size: 12px;
            margin: 0 0 6px;
        }
        .qr-block img {
            width: 60px;
            height: 60px;
        }
        .qr-block .name {
            font-weight: bold;
            margin-top: 4px;
        }
        .qr-block .code {
            color: #777;
            font-size: 9px;
            word-break: break-all;
        }
        .none {
            color: #999;
            font-style: italic;
        }
        .divider {
            border-top: 2px dashed #aaa;
            margin: 16px 0;
        }
    </style>
</head>
<body>
    <h1>Mimo Play Cafe</h1>
    <div class="booking">Booking # {{ $orderItem->ord_code_ph }}</div>

    <div class="qr-block">
        <h3>Child</h3>
        @if($qrChildImage)
            <img src="{{ $qrChildImage }}" alt="Child QR">
        @else
            <p class="none">No QR code</p>
        @endif
        <div class="name">{{ trim(($child->firstname ?? '').' '.($child->lastname ?? '')) }}</div>
        <div class="code">{{ $orderItem->qr_child ?: 'N/A' }}</div>
    </div>

    <div class="divider"></div>

    <div class="qr-block">
        <h3>Guardian</h3>
        @if($qrGuardianImage)
            <img src="{{ $qrGuardianImage }}" alt="Guardian QR">
        @else
            <p class="none">No QR code</p>
        @endif
        <div class="name">{{ $guardian->d_name ?? '' }}</div>
        <div class="code">{{ $orderItem->qr_guardian ?: 'N/A' }}</div>
    </div>
</body>
</html>
