<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesWithPermissions;
use App\Filament\Resources\RepCollectionResource\Pages;
use App\Models\RepCollection;
use App\Services\RepCollectionReceiptService;
use App\Services\RepCollectionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RepCollectionResource extends Resource
{
    use AuthorizesWithPermissions;

    protected static ?string $model = RepCollection::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Sales Rep Module';

    protected static ?int $navigationSort = 2;

    protected static string $permissionModule = 'rep_collections';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('sales_rep_id')
                    ->relationship('salesRep', 'name')->required()->searchable()->preload(),
                Forms\Components\Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')->required()->searchable()->preload(),
                Forms\Components\Select::make('zone_id')
                    ->relationship('zone', 'name')->searchable()->preload(),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')->searchable()->preload(),
                Forms\Components\DatePicker::make('entry_date')->required()->default(now()),
                Forms\Components\Select::make('mode')
                    ->options(['cash' => 'Cash', 'cheque' => 'Cheque', 'bank_transfer' => 'Bank Transfer'])
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('amount')->required()->numeric()->prefix('LKR '),
                Forms\Components\TextInput::make('reference'),
                Forms\Components\Select::make('cheque_received_id')
                    ->relationship('chequeReceived', 'cheque_no')->searchable()->preload()
                    ->visible(fn (Forms\Get $get) => $get('mode') === 'cheque'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('salesRep.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer.name')->searchable(),
                Tables\Columns\TextColumn::make('entry_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('mode')->badge(),
                Tables\Columns\TextColumn::make('amount')->money('lkr')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'pending' => 'gray',
                    'handed_over' => 'warning',
                    'confirmed' => 'success',
                }),
                Tables\Columns\TextColumn::make('cashbookEntry.voucher_no')
                    ->label('Cashbook Voucher')
                    ->placeholder('—')
                    ->url(fn (RepCollection $record) => $record->cashbook_entry_id
                        ? InflowResource::getUrl('edit', ['record' => $record->cashbook_entry_id])
                        : null),
            ])
            ->defaultSort('entry_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('sales_rep_id')->relationship('salesRep', 'name')->label('Sales Rep'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'handed_over' => 'Handed Over',
                    'confirmed' => 'Confirmed',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (RepCollection $record) => $record->status === 'pending'),
                Tables\Actions\Action::make('printReceipt')
                    ->label('Print Receipt')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->action(fn (RepCollection $record) => app(RepCollectionReceiptService::class)->toPdf($record)),
                Tables\Actions\Action::make('approve')
                    ->label('Approve & Post to Cashbook')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (RepCollection $record) => $record->status !== 'confirmed' && Auth::user()->can('rep_collections.approve'))
                    ->requiresConfirmation()
                    ->modalDescription(fn (RepCollection $record) => in_array($record->mode, ['cash', 'bank_transfer'])
                        ? "This will create an approved cashbook inflow of LKR {$record->amount} for {$record->customer?->name} and update their outstanding balance."
                        : 'This will mark the collection as confirmed. The cheque itself is tracked separately in Cheque Management.')
                    ->action(fn (RepCollection $record) => app(RepCollectionService::class)->approve($record, Auth::user())),
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
            'index' => Pages\ListRepCollections::route('/'),
            'create' => Pages\CreateRepCollection::route('/create'),
            'edit' => Pages\EditRepCollection::route('/{record}/edit'),
        ];
    }
}
