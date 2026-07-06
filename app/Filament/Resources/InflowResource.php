<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InflowResource\Pages;

class InflowResource extends BaseCashbookResource
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-circle';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Inflow';

    protected static ?string $pluralModelLabel = 'Inflows';

    public static function direction(): string
    {
        return 'inflow';
    }

    public static function subtypeOptions(): array
    {
        return [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'cheque_received' => 'Cheque Received',
            'sales_rep_collection' => 'Sales Rep Collection',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInflows::route('/'),
            'create' => Pages\CreateInflow::route('/create'),
            'edit' => Pages\EditInflow::route('/{record}/edit'),
        ];
    }
}
