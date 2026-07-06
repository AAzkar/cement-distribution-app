<?php

namespace App\Filament\Resources\IncentiveRuleResource\Pages;

use App\Filament\Resources\IncentiveRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIncentiveRule extends EditRecord
{
    protected static string $resource = IncentiveRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
