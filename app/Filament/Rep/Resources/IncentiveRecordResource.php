<?php

namespace App\Filament\Rep\Resources;

use App\Filament\Rep\Concerns\ScopedToCurrentRep;
use App\Filament\Rep\Resources\IncentiveRecordResource\Pages;
use App\Models\IncentiveRecord;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IncentiveRecordResource extends Resource
{
    use ScopedToCurrentRep;

    protected static ?string $model = IncentiveRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'My Incentives';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('record_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('incentiveRule.name')->label('Rule'),
                Tables\Columns\TextColumn::make('metric_value')->money('lkr'),
                Tables\Columns\TextColumn::make('final_amount')->money('lkr'),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->defaultSort('record_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncentiveRecords::route('/'),
        ];
    }
}
