<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 18px; margin-bottom: 0; }
        .meta { color: #6b7280; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
        th { background-color: #f3f4f6; }
        .text-right { text-align: right; }
        tfoot td { font-weight: bold; background-color: #f9fafb; }
    </style>
</head>
<body>
    <h1>Customer Summary Report</h1>
    <div class="meta">{{ $from->toFormattedDateString() }} &ndash; {{ $to->toFormattedDateString() }}</div>

    <table>
        <thead>
            <tr>
                <th>Customer</th>
                <th>Code</th>
                <th>Zone</th>
                <th>Warehouse</th>
                <th class="text-right">Orders</th>
                <th class="text-right">Orders Total</th>
                <th class="text-right">Collections</th>
                <th class="text-right">Outstanding</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($customers as $customer)
                <tr>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->code }}</td>
                    <td>{{ $customer->zone?->name }}</td>
                    <td>{{ $customer->warehouse?->name }}</td>
                    <td class="text-right">{{ $customer->orders_count ?? 0 }}</td>
                    <td class="text-right">LKR {{ number_format($customer->orders_total ?? 0, 2) }}</td>
                    <td class="text-right">LKR {{ number_format(($customer->cash_collections_total ?? 0) + ($customer->cheque_collections_total ?? 0), 2) }}</td>
                    <td class="text-right">LKR {{ number_format($customer->outstandingBalance(), 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No customers found.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Totals</td>
                <td class="text-right">{{ $customers->sum('orders_count') }}</td>
                <td class="text-right">LKR {{ number_format($customers->sum('orders_total'), 2) }}</td>
                <td class="text-right">LKR {{ number_format($customers->sum(fn ($c) => ($c->cash_collections_total ?? 0) + ($c->cheque_collections_total ?? 0)), 2) }}</td>
                <td class="text-right">LKR {{ number_format($customers->sum(fn ($c) => $c->outstandingBalance()), 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
