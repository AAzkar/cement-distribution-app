<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesWithPermissions;
use App\Filament\Resources\IncentiveRuleResource\Pages;
use App\Models\IncentiveRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IncentiveRuleResource extends Resource
{
    use AuthorizesWithPermissions;

    protected static ?string $model = IncentiveRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Incentives';

    protected static ?int $navigationSort = 1;

    protected static string $permissionModule = 'incentive_rules';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255)->columnSpanFull(),
                Forms\Components\Select::make('metric')
                    ->options([
                        'sales' => 'Total Sales',
                        'collections' => 'Total Collections',
                        'invoice_count' => 'Invoice / Order Count',
                    ])
                    ->required(),
                Forms\Components\Select::make('rule_type')
                    ->options([
                        'slab' => 'Target-based Slabs',
                        'fixed' => 'Fixed Daily Allowance',
                        'percentage' => 'Percentage-based',
                    ])
                    ->required()
                    ->live(),
                Forms\Components\Select::make('allowance_type')
                    ->options([
                        'fuel' => 'Fuel Allowance',
                        'food' => 'Food Allowance',
                        'other' => 'Other Allowance',
                        'bonus' => 'Bonus',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('min_target')
                    ->numeric()
                    ->prefix('LKR ')
                    ->helperText('Minimum metric value required to qualify')
                    ->visible(fn (Forms\Get $get) => in_array($get('rule_type'), ['fixed', 'percentage'])),
                Forms\Components\TextInput::make('fixed_amount')
                    ->numeric()->prefix('LKR ')
                    ->visible(fn (Forms\Get $get) => $get('rule_type') === 'fixed'),
                Forms\Components\TextInput::make('percentage')
                    ->numeric()->suffix('%')
                    ->visible(fn (Forms\Get $get) => $get('rule_type') === 'percentage'),
                Forms\Components\Repeater::make('slabs')
                    ->schema([
                        Forms\Components\TextInput::make('min_value')->numeric()->required()->prefix('LKR ')->label('If metric >='),
                        Forms\Components\TextInput::make('amount')->numeric()->required()->prefix('LKR ')->label('Then allowance ='),
                    ])
                    ->columns(2)
                    ->visible(fn (Forms\Get $get) => $get('rule_type') === 'slab')
                    ->columnSpanFull(),
                Forms\Components\Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')->searchable()->preload()
                    ->helperText('Leave blank to apply to all warehouses'),
                Forms\Components\Select::make('zone_id')
                    ->relationship('zone', 'name')->searchable()->preload()
                    ->helperText('Leave blank to apply to all zones'),
                Forms\Components\Toggle::make('is_active')->default(true),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('metric')->badge(),
                Tables\Columns\TextColumn::make('rule_type')->badge(),
                Tables\Columns\TextColumn::make('allowance_type')->badge(),
                Tables\Columns\TextColumn::make('warehouse.name')->placeholder('All'),
                Tables\Columns\TextColumn::make('zone.name')->placeholder('All'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncentiveRules::route('/'),
            'create' => Pages\CreateIncentiveRule::route('/create'),
            'edit' => Pages\EditIncentiveRule::route('/{record}/edit'),
        ];
    }
}
