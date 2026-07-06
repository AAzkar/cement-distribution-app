<?php

namespace App\Filament\Rep\Resources;

use App\Filament\Rep\Concerns\ScopedToCurrentRep;
use App\Filament\Rep\Resources\HandoverResource\Pages;
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
    use ScopedToCurrentRep;

    protected static ?string $model = Handover::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationLabel = 'My Handovers';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        $rep = Auth::user();

        return $form
            ->schema([
                Forms\Components\Select::make('warehouse_id')
                    ->relationship('warehouse', 'name', fn ($query) => $query->whereIn('id', $rep->warehouses()->pluck('warehouses.id')))
                    ->required()
                    ->default(fn () => $rep->warehouses()->first()?->id),
                Forms\Components\DatePicker::make('handover_date')->required()->default(now()),
                Forms\Components\Textarea::make('notes')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('handover_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('cash_total')->money('lkr'),
                Tables\Columns\TextColumn::make('cheque_total')->money('lkr'),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->defaultSort('handover_date', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (Handover $record) => $record->status === 'draft'),
                Tables\Actions\Action::make('submit')
                    ->label('Submit Handover')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn (Handover $record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(fn (Handover $record) => app(HandoverService::class)->submit($record)),
            ]);
    }

    public static function mutateFormDataBeforeCreateHook(array $data): array
    {
        $data['sales_rep_id'] = Auth::id();
        $data['status'] = 'draft';

        return $data;
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
