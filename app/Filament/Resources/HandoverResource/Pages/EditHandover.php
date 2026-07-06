<?php

namespace App\Filament\Resources\HandoverResource\Pages;

use App\Filament\Resources\HandoverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHandover extends EditRecord
{
    protected static string $resource = HandoverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
