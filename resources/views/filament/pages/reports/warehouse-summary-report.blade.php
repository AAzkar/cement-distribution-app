<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Report Period</x-slot>

        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="text-sm font-medium">Period</label>
                <select wire:model.live="periodType" class="fi-select-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700">
                    <option value="month">Specific Month</option>
                    <option value="year">Specific Year</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>

            @if ($periodType === 'month')
                <div>
                    <label class="text-sm font-medium">Month</label>
                    <select wire:model.live="month" class="fi-select-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700">
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium">Year</label>
                    <select wire:model.live="year" class="fi-select-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700">
                        @foreach ($this->years() as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            @elseif ($periodType === 'year')
                <div>
                    <label class="text-sm font-medium">Year</label>
                    <select wire:model.live="year" class="fi-select-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700">
                        @foreach ($this->years() as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div>
                    <label class="text-sm font-medium">From</label>
                    <input type="date" wire:model.live="dateFrom" class="fi-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700" />
                </div>
                <div>
                    <label class="text-sm font-medium">To</label>
                    <input type="date" wire:model.live="dateTo" class="fi-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700" />
                </div>
            @endif
        </div>
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>
