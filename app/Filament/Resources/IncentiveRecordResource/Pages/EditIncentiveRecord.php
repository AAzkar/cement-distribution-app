<?php

namespace App\Filament\Resources\IncentiveRecordResource\Pages;

use App\Filament\Resources\IncentiveRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIncentiveRecord extends EditRecord
{
    protected static string $resource = IncentiveRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
