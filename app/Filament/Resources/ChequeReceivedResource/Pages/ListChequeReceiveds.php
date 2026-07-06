<?php

namespace App\Filament\Resources\ChequeReceivedResource\Pages;

use App\Filament\Resources\ChequeReceivedResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChequeReceiveds extends ListRecords
{
    protected static string $resource = ChequeReceivedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
