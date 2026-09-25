<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #333333;
            margin: 0;
            padding: 0;
        }

        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        td {
            padding: 5px;
            vertical-align: top;
        }

        .header-table td {
            padding: 0;
        }

        .logo {
            font-size: 26px;
            font-weight: 800;
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: 1px;
            line-height: 1;
        }

        .logo-sub {
            font-size: 9px;
            color: #6b7280;
            margin-top: 5px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .company-details {
            text-align: right;
            font-size: 11px;
            color: #4b5563;
            line-height: 1.4;
        }

        .invoice-title {
            font-size: 22px;
            font-weight: 800;
            color: #111827;
            text-align: right;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .divider {
            border-top: 2px solid #f3f4f6;
            margin: 15px 0;
        }

        .section-title {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            color: #9ca3af;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .billing-shipping {
            font-size: 12px;
            line-height: 1.4;
        }

        .items-table {
            margin-top: 20px;
        }

        .items-table th {
            background-color: #2563eb;
            color: #ffffff;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 12px;
            text-align: left;
            border: none;
        }

        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 12px;
        }

        .items-table tr:last-child td {
            border-bottom: 2px solid #e5e7eb;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals-table {
            width: 40%;
            float: right;
            margin-top: 10px;
        }

        .totals-table td {
            padding: 6px 12px;
            font-size: 12px;
        }

        .totals-table tr.total-row td {
            font-weight: 800;
            font-size: 15px;
            color: #2563eb;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 9999px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .footer {
            margin-top: 60px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
            padding-top: 15px;
        }
    </style>
</head>

<body>
    <div class="invoice-box">
        <!-- Top Header: Logo & Company Address -->
        <table class="header-table">
            <tr>
                <td>
                    <div class="logo">{{ $company['name'] ?? 'Unknown' }}</div>
                    <div class="logo-sub">ORDER INVOICE</div>
                </td>
                <td class="company-details">
                    @if(isset($company['address_line1']) && $company['address_line1'])
                    {{ $company['address_line1'] }}<br>
                    @endif
                    @if(isset($company['address_line2']) && $company['address_line2'])
                    {{ $company['address_line2'] }}<br>
                    @endif
                    @if(isset($company['phone']) && $company['phone'])
                    Phone: {{ $company['phone'] }}<br>
                    @endif
                    @if(isset($company['email']) && $company['email'])
                    Email: {{ $company['email'] }}
                    @endif
                </td>
            </tr>
        </table>

        <div class="divider"></div>

        <!-- Invoice Info & Metadata -->
        <table>
            <tr>
                <td>
                    {{-- Customer Info --}}
                    <div class="section-title">Customer Info</div>
                    <div class="billing-shipping" style="margin-bottom: 14px;">
                        @php
                        $customer = $order->user ?? null;
                        $customerName = $customer->name ?? $order->address->name ?? ($order->guest_name ?? 'Unknown');
                        $customerPhone = $customer->phone ?? $order->address->phone ?? $order->guest_phone ?? null;
                        @endphp
                        <strong>{{ $customerName }}</strong><br>
                        @if($customerPhone)
                        {{ $customerPhone }}
                        @endif
                    </div>

                    {{-- Delivery Address --}}
                    @php
                    $addr = $order->address ?? null;
                    $addrParts = $addr ? array_filter([
                    $addr->address_line_1,
                    $addr->address_line_2,
                    $addr->village,
                    $addr->commune,
                    $addr->district,
                    $addr->province,
                    ]) : [];
                    @endphp
                    <div class="section-title">Delivery Address</div>
                    <div class="billing-shipping">
                        @if($addr)
                        <strong>{{ $addr->name }}</strong>@if($addr->phone) &mdash; {{ $addr->phone }}@endif<br>
                        @if($addr->address_line_1)<span style="color:#6b7280;">Address 1:</span> {{ $addr->address_line_1 }}<br>@endif
                        @if($addr->address_line_2)<span style="color:#6b7280;">Address 2:</span> {{ $addr->address_line_2 }}<br>@endif
                        @if($addr->village)<span style="color:#6b7280;">Village:</span> {{ $addr->village }}<br>@endif
                        @if($addr->commune)<span style="color:#6b7280;">Commune:</span> {{ $addr->commune }}<br>@endif
                        @if($addr->district)<span style="color:#6b7280;">District:</span> {{ $addr->district }}<br>@endif
                        @if($addr->province)<span style="color:#6b7280;">Province:</span> {{ $addr->province }}<br>@endif
                        @if($addr->notes)<span style="color:#6b7280;">Notes:</span> {{ $addr->notes }}@endif
                        @else
                        <span style="color:#9ca3af; font-style: italic;">No address provided</span>
                        @endif
                    </div>
                </td>
                <td style="text-align: right; width: 45%;">
                    <div class="invoice-title">Invoice</div>
                    <table style="width: 100%; font-size: 11px; margin-bottom: 0;">
                        <tr>
                            <td class="text-right" style="color: #6b7280; font-weight: 600; padding: 2px 0;">Invoice No:</td>
                            <td class="text-right" style="font-weight: 700; color: #111827; padding: 2px 0;">#{{ $order->order_number }}</td>
                        </tr>
                        <tr>
                            <td class="text-right" style="color: #6b7280; font-weight: 600; padding: 2px 0;">Date:</td>
                            <td class="text-right" style="color: #111827; padding: 2px 0;">{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-right" style="color: #6b7280; font-weight: 600; padding: 2px 0;">Order Status:</td>
                            <td class="text-right" style="padding: 2px 0;">
                                <span class="badge {{ $order->status->value === 'completed' || $order->status->value === 'delivered' ? 'badge-success' : ($order->status->value === 'cancelled' ? 'badge-danger' : 'badge-info') }}">
                                    {{ $order->status->value ?? $order->status }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right" style="color: #6b7280; font-weight: 600; padding: 2px 0;">Payment:</td>
                            <td class="text-right" style="padding: 2px 0;">
                                <span class="badge {{ $order->payment_status->value === 'paid' ? 'badge-success' : 'badge-warning' }}">
                                    {{ $order->payment_status->value ?? $order->payment_status }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Invoice Items Table -->
        <table class="items-table" style="table-layout: fixed; width: 100%;">
            <colgroup>
                <col style="width: 48%;">
                <col style="width: 12%;">
                <col style="width: 20%;">
                <col style="width: 20%;">
            </colgroup>
            <thead>
                <tr>
                    <th style="text-align: left;">Product</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Unit Price</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td style="text-align: left;">
                        <strong>{{ $item->product->name ?? $item->product_name ?? 'Unknown' }}</strong>
                    </td>
                    <td style="text-align: center;">{{ $item->quantity }}</td>
                    <td style="text-align: right;">${{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align: right; font-weight: 700;">${{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals Table -->
        <table class="totals-table">
            <tr>
                <td style="color: #6b7280;">Subtotal:</td>
                <td class="text-right font-semibold">${{ number_format($order->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td style="color: #6b7280;">Shipping:</td>
                <td class="text-right font-semibold">
                    @if($order->shipping_cost > 0)
                    ${{ number_format($order->shipping_cost, 2) }}
                    @else
                    Free
                    @endif
                </td>
            </tr>
            <tr class="total-row">
                <td>Total:</td>
                <td class="text-right">${{ number_format($order->total, 2) }}</td>
            </tr>
        </table>

        <div style="clear: both;"></div>

        <!-- Order Notes -->
        @if(isset($order->meta['notes']) && $order->meta['notes'])
        <div style="margin-top: 30px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px;">
            <div class="section-title" style="margin-bottom: 5px;">Customer Notes</div>
            <div style="font-size: 11px; color: #4b5563; font-style: italic;">
                "{{ $order->meta['notes'] }}"
            </div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            Thank you for your order!<br>
            @if(!empty($company['email']))
            If you have any questions, contact us via email at {{ $company['email'] }}.
            @endif
        </div>
    </div>
</body>

</html>