<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'cashbookEntries';

    protected static ?string $title = 'Payments Received';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('voucher_no')
            ->columns([
                Tables\Columns\TextColumn::make('voucher_no'),
                Tables\Columns\TextColumn::make('entry_date')->date(),
                Tables\Columns\TextColumn::make('amount')->money('lkr'),
                Tables\Columns\TextColumn::make('paymentMode.name'),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->modifyQueryUsing(fn ($query) => $query->where('direction', 'inflow'))
            ->defaultSort('entry_date', 'desc');
    }
}
