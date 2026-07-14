@php
    $customer = $this->getRecord();
    $ledger = $this->ledger();
    $aging = $this->aging();
    $overage = $customer->creditLimitExceededBy(0);
@endphp

<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">{{ $customer->name }}</x-slot>
        <x-slot name="description">{{ $customer->code }}</x-slot>

        <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Opening Balance</dt>
                <dd class="font-medium">LKR {{ number_format($ledger['opening_balance'], 2) }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Closing Balance</dt>
                <dd class="font-semibold {{ $ledger['closing_balance'] > 0 ? 'text-danger-600' : 'text-success-600' }}">
                    LKR {{ number_format($ledger['closing_balance'], 2) }}
                </dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Credit Limit</dt>
                <dd class="font-medium">
                    @if ($customer->credit_limit === null)
                        No limit
                    @else
                        LKR {{ number_format($customer->credit_limit, 2) }}
                        @if ($overage)
                            <x-filament::badge color="danger">Exceeded by LKR {{ number_format($overage, 2) }}</x-filament::badge>
                        @endif
                    @endif
                </dd>
            </div>
        </dl>
    </x-filament::section>

    <div class="mt-6 space-y-6">
        @include('filament.customers._ledger', ['ledger' => $ledger, 'aging' => $aging])
    </div>
</x-filament-panels::page>
