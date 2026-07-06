<?php

namespace App\Filament\Resources\IncentiveRuleResource\Pages;

use App\Filament\Resources\IncentiveRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIncentiveRules extends ListRecords
{
    protected static string $resource = IncentiveRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
