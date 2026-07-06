<?php

namespace App\Filament\Resources\InflowResource\Pages;

use App\Filament\Resources\InflowResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInflow extends CreateRecord
{
    protected static string $resource = InflowResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return InflowResource::mutateFormDataBeforeCreateHook($data);
    }
}
