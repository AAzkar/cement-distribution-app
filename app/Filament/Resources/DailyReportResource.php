<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesWithPermissions;
use App\Filament\Resources\DailyReportResource\Pages;
use App\Models\DailyReport;
use App\Services\DailyReportExportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DailyReportResource extends Resource
{
    use AuthorizesWithPermissions;

    protected static ?string $model = DailyReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static string $permissionModule = 'daily_reports';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('report_date')->required()->default(now()),
                Forms\Components\Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')->searchable()->preload()
                    ->helperText('Leave blank for the consolidated report'),
                Forms\Components\TextInput::make('opening_balance')->required()->numeric()->default(0)->prefix('LKR '),
                Forms\Components\TextInput::make('total_inflows')->required()->numeric()->default(0)->prefix('LKR '),
                Forms\Components\TextInput::make('total_outflows')->required()->numeric()->default(0)->prefix('LKR '),
                Forms\Components\TextInput::make('closing_balance')->required()->numeric()->default(0)->prefix('LKR '),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('report_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')->placeholder('Consolidated')->sortable(),
                Tables\Columns\TextColumn::make('opening_balance')->money('lkr'),
                Tables\Columns\TextColumn::make('total_inflows')->money('lkr'),
                Tables\Columns\TextColumn::make('total_outflows')->money('lkr'),
                Tables\Columns\TextColumn::make('closing_balance')->money('lkr'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'draft' => 'gray',
                    'submitted' => 'warning',
                    'approved' => 'success',
                    'locked' => 'danger',
                }),
            ])
            ->defaultSort('report_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')->relationship('warehouse', 'name'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'submitted' => 'Submitted',
                    'approved' => 'Approved',
                    'locked' => 'Locked',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (DailyReport $record) => $record->status === 'draft'),
                Tables\Actions\Action::make('submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn (DailyReport $record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(fn (DailyReport $record) => $record->update([
                        'status' => 'submitted',
                        'submitted_by' => Auth::id(),
                        'submitted_at' => now(),
                    ])),
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (DailyReport $record) => $record->status === 'submitted' && Auth::user()->can('daily_reports.approve'))
                    ->requiresConfirmation()
                    ->action(fn (DailyReport $record) => $record->update([
                        'status' => 'approved',
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                    ])),
                Tables\Actions\Action::make('lock')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (DailyReport $record) => $record->status === 'approved' && Auth::user()->can('daily_reports.approve'))
                    ->requiresConfirmation()
                    ->action(fn (DailyReport $record) => $record->update(['status' => 'locked'])),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('downloadPdf')
                        ->label('Download PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(fn (DailyReport $record) => app(DailyReportExportService::class)->toPdf($record)),
                    Tables\Actions\Action::make('downloadExcel')
                        ->label('Download Excel')
                        ->icon('heroicon-o-table-cells')
                        ->action(fn (DailyReport $record) => app(DailyReportExportService::class)->toExcel($record)),
                ])->label('Export')->icon('heroicon-o-arrow-down-tray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyReports::route('/'),
            'create' => Pages\CreateDailyReport::route('/create'),
            'edit' => Pages\EditDailyReport::route('/{record}/edit'),
            'view' => Pages\ViewDailyReport::route('/{record}'),
        ];
    }
}
