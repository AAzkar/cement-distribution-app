<?php

namespace App\Filament\Resources\OutflowResource\Pages;

use App\Filament\Resources\OutflowResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOutflow extends CreateRecord
{
    protected static string $resource = OutflowResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return OutflowResource::mutateFormDataBeforeCreateHook($data);
    }
}
