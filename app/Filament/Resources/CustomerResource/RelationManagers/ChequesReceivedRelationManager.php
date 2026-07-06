<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ChequesReceivedRelationManager extends RelationManager
{
    protected static string $relationship = 'chequesReceived';

    protected static ?string $title = 'Cheques';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('cheque_no')
            ->columns([
                Tables\Columns\TextColumn::make('cheque_no'),
                Tables\Columns\TextColumn::make('bank_name'),
                Tables\Columns\TextColumn::make('amount')->money('lkr'),
                Tables\Columns\TextColumn::make('received_date')->date(),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->defaultSort('received_date', 'desc');
    }
}
