<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerLedgerService;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class CustomerStatement extends Page
{
    use InteractsWithRecord;

    protected static string $resource = CustomerResource::class;

    protected static string $view = 'filament.customers.statement';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(CustomerResource::canViewAny(), 403);
    }

    public function ledger(): array
    {
        return app(CustomerLedgerService::class)->buildLedger($this->getRecord());
    }

    public function aging(): array
    {
        return app(CustomerLedgerService::class)->agingBuckets($this->getRecord());
    }

    public function getTitle(): string
    {
        /** @var Customer $customer */
        $customer = $this->getRecord();

        return "Statement — {$customer->name}";
    }
}
