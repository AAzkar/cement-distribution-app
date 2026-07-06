<?php

namespace App\Filament\Rep\Resources\HandoverResource\Pages;

use App\Filament\Rep\Resources\HandoverResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHandover extends CreateRecord
{
    protected static string $resource = HandoverResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return HandoverResource::mutateFormDataBeforeCreateHook($data);
    }
}
