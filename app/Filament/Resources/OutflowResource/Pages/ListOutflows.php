<?php

namespace App\Filament\Resources\OutflowResource\Pages;

use App\Filament\Resources\OutflowResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOutflows extends ListRecords
{
    protected static string $resource = OutflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
