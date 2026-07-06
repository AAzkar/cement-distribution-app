<?php

namespace App\Filament\Resources\IncentiveRecordResource\Pages;

use App\Filament\Resources\IncentiveRecordResource;
use App\Services\IncentiveCalculationService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListIncentiveRecords extends ListRecords
{
    protected static string $resource = IncentiveRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('calculate')
                ->label('Calculate Incentives')
                ->icon('heroicon-o-calculator')
                ->form([
                    Forms\Components\DatePicker::make('date')->required()->default(now()),
                ])
                ->action(function (array $data) {
                    $records = app(IncentiveCalculationService::class)->calculateForDate(Carbon::parse($data['date']));

                    Notification::make()->title("Calculated {$records->count()} incentive record(s)")->success()->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
