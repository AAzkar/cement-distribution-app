<?php

namespace App\Filament\Resources\VoucherSequenceResource\Pages;

use App\Filament\Resources\VoucherSequenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVoucherSequences extends ListRecords
{
    protected static string $resource = VoucherSequenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
