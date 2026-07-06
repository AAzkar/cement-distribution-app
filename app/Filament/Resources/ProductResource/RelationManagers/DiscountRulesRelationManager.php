<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DiscountRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'discountRules';

    protected static ?string $title = 'Bag-Count Discount Slabs';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('min_bags')->required()->numeric()->label('Min Bags'),
                Forms\Components\TextInput::make('max_bags')->numeric()->label('Max Bags')
                    ->helperText('Leave blank for no upper limit'),
                Forms\Components\Select::make('discount_type')
                    ->options(['flat_per_bag' => 'Flat LKR per Bag', 'percentage' => 'Percentage of Rate'])
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('discount_value')->required()->numeric()
                    ->prefix(fn (Forms\Get $get) => $get('discount_type') === 'percentage' ? null : 'LKR ')
                    ->suffix(fn (Forms\Get $get) => $get('discount_type') === 'percentage' ? '%' : null),
                Forms\Components\Toggle::make('is_active')->default(true),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('min_bags')->label('Min Bags'),
                Tables\Columns\TextColumn::make('max_bags')->label('Max Bags')->placeholder('No limit'),
                Tables\Columns\TextColumn::make('discount_type')->badge(),
                Tables\Columns\TextColumn::make('discount_value'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
