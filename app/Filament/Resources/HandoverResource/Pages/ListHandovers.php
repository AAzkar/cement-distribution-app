<?php

namespace App\Filament\Resources\HandoverResource\Pages;

use App\Filament\Resources\HandoverResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHandovers extends ListRecords
{
    protected static string $resource = HandoverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
