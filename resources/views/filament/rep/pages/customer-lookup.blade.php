<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">
                {{ $this->customerRecord->name }}
            </x-slot>
            <x-slot name="description">
                {{ $this->customerRecord->code }} &middot; {{ ucfirst($this->customerRecord->type) }}
            </x-slot>

            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Phone</dt>
                    <dd class="font-medium">{{ $this->customerRecord->phone ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Address</dt>
                    <dd class="font-medium">{{ $this->customerRecord->address ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Zone</dt>
                    <dd class="font-medium">{{ $this->customerRecord->zone?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Outstanding Balance</dt>
                    <dd class="font-semibold {{ $this->customerRecord->outstandingBalance() > 0 ? 'text-danger-600' : 'text-success-600' }}">
                        LKR {{ number_format($this->customerRecord->outstandingBalance(), 2) }}
                    </dd>
                </div>
            </dl>

            <div class="mt-4 flex gap-3">
                <x-filament::button tag="a" :href="$this->newOrderUrl()" icon="heroicon-o-shopping-cart">
                    New Order
                </x-filament::button>
                <x-filament::button tag="a" :href="$this->newCollectionUrl()" color="gray" icon="heroicon-o-banknotes">
                    New Collection
                </x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Recent Orders</x-slot>

            @forelse ($this->recentOrders() as $order)
                <div class="flex items-center justify-between border-b border-gray-100 py-2 text-sm last:border-0 dark:border-gray-700">
                    <div>
                        <div class="font-medium">{{ $order->order_no }}</div>
                        <div class="text-gray-500 dark:text-gray-400">{{ $order->order_date->toFormattedDateString() }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-medium">LKR {{ number_format($order->total_amount, 2) }}</div>
                        <x-filament::badge>{{ ucfirst($order->status) }}</x-filament::badge>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No orders yet.</p>
            @endforelse
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Recent Cheques</x-slot>

            @forelse ($this->recentCheques() as $cheque)
                <div class="flex items-center justify-between border-b border-gray-100 py-2 text-sm last:border-0 dark:border-gray-700">
                    <div>
                        <div class="font-medium">{{ $cheque->cheque_no }} &middot; {{ $cheque->bank_name }}</div>
                        <div class="text-gray-500 dark:text-gray-400">{{ $cheque->received_date->toFormattedDateString() }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-medium">LKR {{ number_format($cheque->amount, 2) }}</div>
                        <x-filament::badge>{{ ucfirst($cheque->status) }}</x-filament::badge>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No cheques yet.</p>
            @endforelse
        </x-filament::section>
    </div>
</x-filament-panels::page>
