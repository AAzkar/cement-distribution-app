<?php

namespace App\Filament\Resources\InflowResource\Pages;

use App\Filament\Resources\InflowResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInflows extends ListRecords
{
    protected static string $resource = InflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
