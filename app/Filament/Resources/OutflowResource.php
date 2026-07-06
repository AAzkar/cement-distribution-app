<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OutflowResource\Pages;

class OutflowResource extends BaseCashbookResource
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-circle';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Outflow';

    protected static ?string $pluralModelLabel = 'Outflows';

    public static function direction(): string
    {
        return 'outflow';
    }

    public static function subtypeOptions(): array
    {
        return [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'expense' => 'Expense',
            'supplier_payment' => 'Supplier Payment',
            'cheque_issued' => 'Cheque Issued',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOutflows::route('/'),
            'create' => Pages\CreateOutflow::route('/create'),
            'edit' => Pages\EditOutflow::route('/{record}/edit'),
        ];
    }
}
