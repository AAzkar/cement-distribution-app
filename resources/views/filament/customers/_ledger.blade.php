@php
    $agingLabels = [
        'current' => '0–30 days',
        'days_31_60' => '31–60 days',
        'days_61_90' => '61–90 days',
        'days_90_plus' => '90+ days',
    ];
@endphp

<x-filament::section>
    <x-slot name="heading">Aging</x-slot>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        @foreach ($agingLabels as $key => $label)
            @php $overdue = $aging[$key] > 0 && $key !== 'current'; @endphp
            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700 {{ $overdue ? 'bg-danger-50 dark:bg-danger-500/10' : '' }}">
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</div>
                <div class="mt-1 text-lg font-semibold {{ $overdue ? 'text-danger-600' : '' }}">
                    LKR {{ number_format($aging[$key], 2) }}
                </div>
            </div>
        @endforeach
    </div>
</x-filament::section>

<x-filament::section class="mt-6">
    <x-slot name="heading">Transactions</x-slot>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    <th class="py-2 pr-4">Date</th>
                    <th class="py-2 pr-4">Type</th>
                    <th class="py-2 pr-4">Reference</th>
                    <th class="py-2 pr-4 text-right">Debit</th>
                    <th class="py-2 pr-4 text-right">Credit</th>
                    <th class="py-2 text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-gray-100 dark:border-gray-800">
                    <td class="py-2 pr-4 text-gray-500 dark:text-gray-400" colspan="5">Opening balance</td>
                    <td class="py-2 text-right font-medium">LKR {{ number_format($ledger['opening_balance'], 2) }}</td>
                </tr>
                @forelse ($ledger['entries'] as $row)
                    <tr class="border-b border-gray-100 last:border-0 dark:border-gray-800">
                        <td class="py-2 pr-4">{{ $row['date']->toFormattedDateString() }}</td>
                        <td class="py-2 pr-4"><x-filament::badge>{{ ucfirst($row['type']) }}</x-filament::badge></td>
                        <td class="py-2 pr-4">{{ $row['description'] }}</td>
                        <td class="py-2 pr-4 text-right">{{ $row['debit'] > 0 ? 'LKR '.number_format($row['debit'], 2) : '—' }}</td>
                        <td class="py-2 pr-4 text-right">{{ $row['credit'] > 0 ? 'LKR '.number_format($row['credit'], 2) : '—' }}</td>
                        <td class="py-2 text-right font-medium">LKR {{ number_format($row['running_balance'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-4 text-center text-gray-500 dark:text-gray-400">No transactions yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament::section>
