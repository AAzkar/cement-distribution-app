<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesWithPermissions;
use App\Filament\Resources\VoucherSequenceResource\Pages;
use App\Models\VoucherSequence;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VoucherSequenceResource extends Resource
{
    use AuthorizesWithPermissions;

    protected static ?string $model = VoucherSequence::class;

    protected static ?string $navigationIcon = 'heroicon-o-hashtag';

    protected static ?string $navigationGroup = 'Master Setup';

    protected static ?int $navigationSort = 7;

    protected static string $permissionModule = 'voucher_sequences';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('key')->required()->helperText('e.g. inflow, outflow'),
                Forms\Components\Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')->searchable()->preload()
                    ->helperText('Leave blank for a global sequence'),
                Forms\Components\TextInput::make('prefix')->required()->maxLength(20),
                Forms\Components\TextInput::make('padding')->required()->numeric()->default(5),
                Forms\Components\TextInput::make('next_number')->required()->numeric()->default(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')->searchable(),
                Tables\Columns\TextColumn::make('warehouse.name')->sortable()->placeholder('Global'),
                Tables\Columns\TextColumn::make('prefix'),
                Tables\Columns\TextColumn::make('padding'),
                Tables\Columns\TextColumn::make('next_number'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVoucherSequences::route('/'),
            'create' => Pages\CreateVoucherSequence::route('/create'),
            'edit' => Pages\EditVoucherSequence::route('/{record}/edit'),
        ];
    }
}
