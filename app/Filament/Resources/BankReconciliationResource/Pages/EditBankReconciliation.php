<?php

namespace App\Filament\Resources\BankReconciliationResource\Pages;

use App\Filament\Resources\BankReconciliationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBankReconciliation extends EditRecord
{
    protected static string $resource = BankReconciliationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
