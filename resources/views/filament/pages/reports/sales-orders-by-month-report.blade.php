@php $rows = $this->rows(); @endphp
<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Filters</x-slot>

        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="text-sm font-medium">Year</label>
                <select wire:model.live="year" class="fi-select-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700">
                    @foreach ($this->years() as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium">Warehouse</label>
                <select wire:model.live="warehouseId" class="fi-select-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700">
                    <option value="">All Warehouses</option>
                    @foreach ($this->warehouses() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Orders by Month &mdash; {{ $year }}</x-slot>

        <div class="overflow-x-auto">
            <table class="fi-ta-table w-full text-start text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10">
                        <th class="px-3 py-2 text-start font-medium">Month</th>
                        <th class="px-3 py-2 text-end font-medium">Orders</th>
                        <th class="px-3 py-2 text-end font-medium">Total Bags</th>
                        <th class="px-3 py-2 text-end font-medium">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr class="border-b border-gray-100 dark:border-white/5">
                            <td class="px-3 py-2">{{ $row['month_name'] }}</td>
                            <td class="px-3 py-2 text-end">{{ $row['orders_count'] }}</td>
                            <td class="px-3 py-2 text-end">{{ number_format($row['total_bags']) }}</td>
                            <td class="px-3 py-2 text-end">LKR {{ number_format($row['total_amount'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-semibold">
                        <td class="px-3 py-2">Total</td>
                        <td class="px-3 py-2 text-end">{{ $rows->sum('orders_count') }}</td>
                        <td class="px-3 py-2 text-end">{{ number_format($rows->sum('total_bags')) }}</td>
                        <td class="px-3 py-2 text-end">LKR {{ number_format($rows->sum('total_amount'), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
