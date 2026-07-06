<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SalesOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'salesOrders';

    protected static ?string $title = 'Sales Orders';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_no')
            ->columns([
                Tables\Columns\TextColumn::make('order_no'),
                Tables\Columns\TextColumn::make('order_date')->date(),
                Tables\Columns\TextColumn::make('total_amount')->money('lkr'),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->defaultSort('order_date', 'desc');
    }
}
