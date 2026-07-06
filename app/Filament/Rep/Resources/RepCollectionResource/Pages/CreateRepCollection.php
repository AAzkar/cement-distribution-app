<?php

namespace App\Filament\Rep\Resources\RepCollectionResource\Pages;

use App\Filament\Rep\Resources\RepCollectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRepCollection extends CreateRecord
{
    protected static string $resource = RepCollectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return RepCollectionResource::mutateFormDataBeforeCreateHook($data);
    }
}
