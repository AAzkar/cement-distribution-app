<?php

namespace App\Filament\Resources\PaymentModeResource\Pages;

use App\Filament\Resources\PaymentModeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentMode extends EditRecord
{
    protected static string $resource = PaymentModeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
