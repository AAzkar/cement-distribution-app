<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        .header { display: table; width: 100%; margin-bottom: 24px; }
        .header .brand { display: table-cell; vertical-align: top; }
        .header .brand img { max-height: 60px; margin-bottom: 6px; }
        .header .brand-name { font-size: 16px; font-weight: bold; }
        .header .brand-tagline { color: #6b7280; font-size: 11px; }
        .header .invoice-meta { display: table-cell; vertical-align: top; text-align: right; }
        .header .invoice-meta h1 { font-size: 20px; margin: 0 0 6px; }
        .parties { display: table; width: 100%; margin-bottom: 20px; }
        .parties .party { display: table-cell; width: 50%; vertical-align: top; }
        .party h3 { font-size: 11px; text-transform: uppercase; color: #6b7280; margin: 0 0 4px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.items th, table.items td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
        table.items th { background-color: #f3f4f6; }
        .text-right { text-align: right; }
        .totals { width: 260px; margin-left: auto; }
        .totals table { width: 100%; }
        .totals td { padding: 4px 0; }
        .totals .grand-total td { font-weight: bold; font-size: 14px; border-top: 2px solid #1f2937; padding-top: 8px; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 4px; background: #f3f4f6; font-size: 11px; }
        .signatures { display: table; width: 100%; margin-top: 60px; }
        .signatures .sig { display: table-cell; width: 50%; }
        .sig .line { border-top: 1px solid #9ca3af; width: 200px; margin-top: 40px; padding-top: 4px; font-size: 11px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            @if ($settings->logoUrl())
                <img src="{{ $settings->logoUrl() }}" alt="Logo">
            @endif
            <div class="brand-name">{{ $settings->company_name }}</div>
            @if ($settings->tagline)
                <div class="brand-tagline">{{ $settings->tagline }}</div>
            @endif
        </div>
        <div class="invoice-meta">
            <h1>Order Receipt</h1>
            <div>Order No: <strong>{{ $order->order_no }}</strong></div>
            <div>Date: {{ $order->order_date->toFormattedDateString() }}</div>
            <div>Status: <span class="badge">{{ ucfirst($order->status) }}</span></div>
        </div>
    </div>

    <div class="parties">
        <div class="party">
            <h3>Customer</h3>
            <div>{{ $order->customer->name }}</div>
            <div>{{ $order->customer->code }}</div>
            @if ($order->customer->phone)
                <div>{{ $order->customer->phone }}</div>
            @endif
            @if ($order->customer->address)
                <div>{{ $order->customer->address }}</div>
            @endif
        </div>
        <div class="party">
            <h3>Fulfilled From</h3>
            <div>{{ $order->warehouse->name }}</div>
            @if ($order->salesRep)
                <div>Sales Rep: {{ $order->salesRep->name }}</div>
            @endif
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Product</th>
                <th class="text-right">Bags</th>
                <th class="text-right">Rate/Bag</th>
                <th class="text-right">Discount/Bag</th>
                <th class="text-right">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->product->name }} ({{ $item->product->unit_label }})</td>
                    <td class="text-right">{{ $item->bag_count }}</td>
                    <td class="text-right">LKR {{ number_format($item->rate_per_bag, 2) }}</td>
                    <td class="text-right">LKR {{ number_format($item->discount_per_bag, 2) }}</td>
                    <td class="text-right">LKR {{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr><td>Subtotal</td><td class="text-right">LKR {{ number_format($order->subtotal, 2) }}</td></tr>
            <tr><td>Discount</td><td class="text-right">-LKR {{ number_format($order->discount_total, 2) }}</td></tr>
            <tr class="grand-total"><td>Total</td><td class="text-right">LKR {{ number_format($order->total_amount, 2) }}</td></tr>
        </table>
    </div>

    @if ($order->notes)
        <p><strong>Notes:</strong> {{ $order->notes }}</p>
    @endif

    <div class="signatures">
        <div class="sig"><div class="line">Customer Signature</div></div>
        <div class="sig"><div class="line">Authorized Signature</div></div>
    </div>
</body>
</html>
