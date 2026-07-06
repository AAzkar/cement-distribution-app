<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesWithPermissions;
use App\Filament\Resources\HandoverResource\Pages;
use App\Models\Handover;
use App\Services\HandoverService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class HandoverResource extends Resource
{
    use AuthorizesWithPermissions;

    protected static ?string $model = Handover::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationGroup = 'Sales Rep Module';

    protected static ?int $navigationSort = 3;

    protected static string $permissionModule = 'handovers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('sales_rep_id')
                    ->relationship('salesRep', 'name')->required()->searchable()->preload(),
                Forms\Components\Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')->required()->searchable()->preload(),
                Forms\Components\DatePicker::make('handover_date')->required()->default(now()),
                Forms\Components\TextInput::make('cash_total')->required()->numeric()->default(0)->prefix('LKR '),
                Forms\Components\TextInput::make('cheque_total')->required()->numeric()->default(0)->prefix('LKR ')->disabled()->dehydrated(),
                Forms\Components\TextInput::make('cheque_count')->required()->numeric()->default(0)->disabled()->dehydrated(),
                Forms\Components\Textarea::make('notes')->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('salesRep.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')->sortable(),
                Tables\Columns\TextColumn::make('handover_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('cash_total')->money('lkr'),
                Tables\Columns\TextColumn::make('cheque_total')->money('lkr'),
                Tables\Columns\TextColumn::make('cheque_count'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'draft' => 'gray',
                    'submitted' => 'warning',
                    'confirmed' => 'success',
                }),
                Tables\Columns\TextColumn::make('confirmedBy.name')->label('Confirmed By'),
            ])
            ->defaultSort('handover_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'submitted' => 'Submitted',
                    'confirmed' => 'Confirmed',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (Handover $record) => $record->status !== 'confirmed'),
                Tables\Actions\Action::make('submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn (Handover $record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(fn (Handover $record) => app(HandoverService::class)->submit($record)),
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm Handover')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Handover $record) => $record->status === 'submitted' && Auth::user()->can('handovers.approve'))
                    ->requiresConfirmation()
                    ->action(fn (Handover $record) => app(HandoverService::class)->confirm($record, Auth::user())),
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
            'index' => Pages\ListHandovers::route('/'),
            'create' => Pages\CreateHandover::route('/create'),
            'edit' => Pages\EditHandover::route('/{record}/edit'),
        ];
    }
}
