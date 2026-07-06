<?php

namespace App\Filament\Resources\DailyReportResource\Pages;

use App\Filament\Resources\DailyReportResource;
use App\Models\DailyReport;
use App\Services\DailyReportExportService;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewDailyReport extends ViewRecord
{
    protected static string $resource = DailyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->action(fn (DailyReport $record) => app(DailyReportExportService::class)->toPdf($record)),
            Actions\Action::make('downloadExcel')
                ->label('Download Excel')
                ->icon('heroicon-o-table-cells')
                ->action(fn (DailyReport $record) => app(DailyReportExportService::class)->toExcel($record)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Summary')->schema([
                TextEntry::make('report_date')->date(),
                TextEntry::make('warehouse.name')->placeholder('Consolidated'),
                TextEntry::make('status')->badge(),
                TextEntry::make('opening_balance')->money('lkr'),
                TextEntry::make('total_inflows')->money('lkr'),
                TextEntry::make('total_outflows')->money('lkr'),
                TextEntry::make('closing_balance')->money('lkr'),
            ])->columns(3),
            Section::make('Cheque Summary')->schema([
                TextEntry::make('received_count')
                    ->label('Received')
                    ->state(fn (DailyReport $record) => (($record->cheques_summary['received']['count'] ?? 0)).' cheque(s) — $'.number_format($record->cheques_summary['received']['amount'] ?? 0, 2)),
                TextEntry::make('deposited_count')
                    ->label('Deposited')
                    ->state(fn (DailyReport $record) => $record->cheques_summary['deposited'] ?? 0),
                TextEntry::make('cleared_count')
                    ->label('Cleared')
                    ->state(fn (DailyReport $record) => $record->cheques_summary['cleared'] ?? 0),
                TextEntry::make('returned_count')
                    ->label('Returned')
                    ->state(fn (DailyReport $record) => $record->cheques_summary['returned'] ?? 0),
                TextEntry::make('issued_count')
                    ->label('Issued')
                    ->state(fn (DailyReport $record) => (($record->cheques_summary['issued']['count'] ?? 0)).' cheque(s) — $'.number_format($record->cheques_summary['issued']['amount'] ?? 0, 2)),
                TextEntry::make('issued_cleared_count')
                    ->label('Issued & Cleared')
                    ->state(fn (DailyReport $record) => $record->cheques_summary['issued_cleared'] ?? 0),
                TextEntry::make('issued_bounced_count')
                    ->label('Issued & Bounced')
                    ->state(fn (DailyReport $record) => $record->cheques_summary['issued_bounced'] ?? 0),
            ])->columns(4)
                ->visible(fn (DailyReport $record) => filled($record->cheques_summary)),
        ]);
    }
}
