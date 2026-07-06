<?php

namespace App\Filament\Resources\InflowResource\Pages;

use App\Filament\Resources\InflowResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInflow extends EditRecord
{
    protected static string $resource = InflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
