<?php

namespace App\Filament\Rep\Resources\SalesOrderResource\Pages;

use App\Filament\Rep\Resources\SalesOrderResource;
use App\Services\SalesOrderService;
use Filament\Resources\Pages\EditRecord;

class EditSalesOrder extends EditRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function afterSave(): void
    {
        app(SalesOrderService::class)->recalculateTotals($this->record);
    }
}
