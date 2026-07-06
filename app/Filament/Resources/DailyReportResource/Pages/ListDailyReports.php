<?php

namespace App\Filament\Resources\DailyReportResource\Pages;

use App\Filament\Resources\DailyReportResource;
use App\Services\DailyReportService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListDailyReports extends ListRecords
{
    protected static string $resource = DailyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate')
                ->label('Generate Report')
                ->icon('heroicon-o-sparkles')
                ->form([
                    Forms\Components\DatePicker::make('report_date')->required()->default(now()),
                    Forms\Components\Select::make('warehouse_id')
                        ->relationship('warehouse', 'name')
                        ->helperText('Leave blank to generate the consolidated report'),
                ])
                ->action(function (array $data) {
                    app(DailyReportService::class)->generate(
                        Carbon::parse($data['report_date']),
                        $data['warehouse_id'] ?? null
                    );

                    Notification::make()->title('Report generated')->success()->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
