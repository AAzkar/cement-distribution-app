<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesWithPermissions;
use App\Filament\Resources\DiscountRuleResource\Pages;
use App\Models\DiscountRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DiscountRuleResource extends Resource
{
    use AuthorizesWithPermissions;

    protected static ?string $model = DiscountRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Sales & Inventory';

    protected static ?int $navigationSort = 2;

    protected static string $permissionModule = 'discount_rules';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')->searchable()->preload()
                    ->helperText('Leave blank to apply to all products'),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')->placeholder('All Products')->sortable(),
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('min_bags')->label('Min Bags'),
                Tables\Columns\TextColumn::make('max_bags')->label('Max Bags')->placeholder('No limit'),
                Tables\Columns\TextColumn::make('discount_type')->badge(),
                Tables\Columns\TextColumn::make('discount_value'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')->relationship('product', 'name')->label('Product'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListDiscountRules::route('/'),
            'create' => Pages\CreateDiscountRule::route('/create'),
            'edit' => Pages\EditDiscountRule::route('/{record}/edit'),
        ];
    }
}
