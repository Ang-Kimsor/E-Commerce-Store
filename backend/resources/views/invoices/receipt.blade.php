<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Receipt #{{ $order->order_number }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            line-height: 1.3;
            color: #000000;
            margin: 0;
            padding: 4mm;
            width: 72mm; /* 80mm total width minus padding */
            background-color: #ffffff;
        }

        .receipt-box {
            width: 100%;
            padding: 0;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
            white-space: nowrap;
        }

        .header {
            margin-bottom: 12px;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .subtitle {
            font-size: 9px;
            color: #555555;
            margin-bottom: 6px;
        }

        .divider {
            border-top: 1px dashed #000000;
            margin: 8px 0;
        }

        .info-table,
        .items-table,
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td,
        .items-table td,
        .items-table th,
        .totals-table td {
            padding: 3px 0;
            font-size: 10px;
            vertical-align: top;
        }

        .items-table th {
            text-align: left;
            font-weight: bold;
            border-bottom: 1px dashed #000000;
        }

        .items-table td {
            border-bottom: 1px dashed #e0e0e0;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .totals-table {
            margin-top: 6px;
        }

        .totals-table td {
            padding: 3px 0;
        }

        .total-row td {
            font-weight: bold;
            font-size: 12px;
            border-top: 1px dashed #000000;
            padding-top: 6px;
        }

        .footer {
            margin-top: 6px;
            font-size: 9px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="receipt-box">
        <!-- Header -->
        <div class="header text-center">
            <div class="title">{{ $company['name'] ?? 'Unknown' }}</div>
            @if(!empty($company['email']))
            <div>{{ $company['email'] }}</div>
            @endif
            @if(!empty($company['phone']))
            <div>{{ $company['phone'] }}</div>
            @endif
        </div>

        <div class="divider"></div>

        <!-- Meta info -->
        <table class="info-table">
            <tr>
                <td class="bold">Receipt No:</td>
                <td class="text-right">#{{ $order->order_number }}</td>
            </tr>
            <tr>
                <td class="bold">Date:</td>
                <td class="text-right">{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y H:i') }}</td>
            </tr>
            <tr>
                <td class="bold">Customer:</td>
                <td class="text-right">{{ $order->address->name ?? $order->user->name ?? 'Unknown' }}</td>
            </tr>
            @if($order->address && $order->address->phone)
            <tr>
                <td class="bold">Phone:</td>
                <td class="text-right">{{ $order->address->phone }}</td>
            </tr>
            @endif
            @if($order->address && $order->address->full_address)
            <tr>
                <td class="bold">Address:</td>
                <td class="text-right">{{ $order->address->full_address }}</td>
            </tr>
            @endif
        </table>

        <div class="divider"></div>

        <!-- Items -->
        <table class="items-table" style="table-layout: fixed; width: 100%;">
            <colgroup>
                <col style="width: 60%;">
                <col style="width: 15%;">
                <col style="width: 25%;">
            </colgroup>
            <thead>
                <tr>
                    <th style="text-align: left;">Item</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td style="text-align: left;">
                        {{ $item->product->name ?? $item->product_name ?? 'Unknown' }}
                    </td>
                    <td style="text-align: center; vertical-align: middle;">{{ $item->quantity }}</td>
                    <td style="text-align: right; vertical-align: middle;">${{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="divider"></div>

        <!-- Totals -->
        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">${{ number_format($order->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td>Shipping:</td>
                <td class="text-right">
                    @if($order->shipping_cost > 0)
                    ${{ number_format($order->shipping_cost, 2) }}
                    @else
                    Free
                    @endif
                </td>
            </tr>
            <tr class="total-row">
                <td>TOTAL:</td>
                <td class="text-right">${{ number_format($order->total, 2) }}</td>
            </tr>
        </table>

        <!-- Notes -->
        @if(isset($order->meta['notes']) && $order->meta['notes'])
        <div class="divider"></div>
        <div style="font-size: 9px;">
            <span class="bold">Note:</span> "{{ $order->meta['notes'] }}"
        </div>
        @endif

        <div class="divider"></div>

        <!-- Footer -->
        <div class="footer">
            Thank you for shopping with us!<br>
            Please come again!
        </div>
    </div>
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>

</html>