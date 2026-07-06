<?php

namespace App\Filament\Rep\Resources\SalesOrderResource\Pages;

use App\Filament\Rep\Resources\SalesOrderResource;
use App\Services\SalesOrderService;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesOrder extends CreateRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SalesOrderResource::mutateFormDataBeforeCreateHook($data);
    }

    protected function afterCreate(): void
    {
        app(SalesOrderService::class)->recalculateTotals($this->record);
    }
}
