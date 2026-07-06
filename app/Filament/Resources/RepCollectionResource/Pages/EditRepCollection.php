<?php

namespace App\Filament\Resources\RepCollectionResource\Pages;

use App\Filament\Resources\RepCollectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRepCollection extends EditRecord
{
    protected static string $resource = RepCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
