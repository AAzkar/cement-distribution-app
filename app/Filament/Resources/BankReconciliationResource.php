<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesWithPermissions;
use App\Filament\Resources\BankReconciliationResource\Pages;
use App\Models\BankReconciliation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BankReconciliationResource extends Resource
{
    use AuthorizesWithPermissions;

    protected static ?string $model = BankReconciliation::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Banking';

    protected static ?int $navigationSort = 2;

    protected static string $permissionModule = 'bank_reconciliations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('bank_account_id')
                    ->relationship('bankAccount', 'account_name')->required()->searchable()->preload(),
                Forms\Components\DatePicker::make('period_start')->required(),
                Forms\Components\DatePicker::make('period_end')->required(),
                Forms\Components\TextInput::make('statement_balance')->required()->numeric()->prefix('LKR '),
                Forms\Components\TextInput::make('book_balance')->numeric()->prefix('LKR '),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bankAccount.account_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('period_start')->date()->sortable(),
                Tables\Columns\TextColumn::make('period_end')->date()->sortable(),
                Tables\Columns\TextColumn::make('statement_balance')->money('lkr'),
                Tables\Columns\TextColumn::make('book_balance')->money('lkr'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'completed' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('completedBy.name')->label('Completed By'),
            ])
            ->defaultSort('period_end', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('bank_account_id')->relationship('bankAccount', 'account_name'),
                Tables\Filters\SelectFilter::make('status')->options(['draft' => 'Draft', 'completed' => 'Completed']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (BankReconciliation $record) => $record->status !== 'completed'),
                Tables\Actions\Action::make('complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (BankReconciliation $record) => $record->status !== 'completed' && Auth::user()->can('bank_reconciliations.approve'))
                    ->requiresConfirmation()
                    ->action(fn (BankReconciliation $record) => $record->update([
                        'status' => 'completed',
                        'completed_by' => Auth::id(),
                        'completed_at' => now(),
                    ])),
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
            'index' => Pages\ListBankReconciliations::route('/'),
            'create' => Pages\CreateBankReconciliation::route('/create'),
            'edit' => Pages\EditBankReconciliation::route('/{record}/edit'),
        ];
    }
}
