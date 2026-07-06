<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesWithPermissions;
use App\Filament\Resources\ChequeReceivedResource\Pages;
use App\Models\ChequeReceived;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ChequeReceivedResource extends Resource
{
    use AuthorizesWithPermissions;

    protected static ?string $model = ChequeReceived::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationGroup = 'Cheque Management';

    protected static ?int $navigationSort = 1;

    protected static string $permissionModule = 'cheques_received';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')->required()->searchable()->preload(),
                Forms\Components\Select::make('sales_rep_id')
                    ->relationship('salesRep', 'name')->searchable()->preload()
                    ->label('Collected By (Sales Rep)'),
                Forms\Components\TextInput::make('bank_name')->required()->maxLength(255),
                Forms\Components\TextInput::make('cheque_no')->required()->maxLength(255),
                Forms\Components\TextInput::make('amount')->numeric()->required()->prefix('LKR '),
                Forms\Components\DatePicker::make('received_date')->required()->default(now()),
                Forms\Components\DatePicker::make('deposit_date'),
                Forms\Components\Select::make('bank_account_id')
                    ->relationship('bankAccount', 'account_name')->searchable()->preload()
                    ->label('Deposited To'),
                Forms\Components\Select::make('status')
                    ->options([
                        'received' => 'Received',
                        'deposited' => 'Deposited',
                        'cleared' => 'Cleared',
                        'returned' => 'Returned',
                    ])
                    ->default('received')
                    ->required(),
                Forms\Components\TextInput::make('returned_reason')
                    ->visible(fn (Forms\Get $get) => $get('status') === 'returned'),
                Forms\Components\DatePicker::make('returned_date')
                    ->visible(fn (Forms\Get $get) => $get('status') === 'returned'),
                Forms\Components\Textarea::make('notes')->columnSpanFull(),
                Forms\Components\SpatieMediaLibraryFileUpload::make('attachments')
                    ->collection('attachments')
                    ->multiple()
                    ->acceptedFileTypes(['image/*', 'application/pdf'])
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('bank_name')->searchable(),
                Tables\Columns\TextColumn::make('cheque_no')->searchable(),
                Tables\Columns\TextColumn::make('amount')->money('lkr')->sortable(),
                Tables\Columns\TextColumn::make('received_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('deposit_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'received' => 'gray',
                    'deposited' => 'warning',
                    'cleared' => 'success',
                    'returned' => 'danger',
                }),
            ])
            ->defaultSort('received_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'received' => 'Received',
                    'deposited' => 'Deposited',
                    'cleared' => 'Cleared',
                    'returned' => 'Returned',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('markDeposited')
                    ->label('Mark Deposited')
                    ->icon('heroicon-o-banknotes')
                    ->visible(fn (ChequeReceived $record) => $record->status === 'received' && Auth::user()->can('cheques_received.edit'))
                    ->form([
                        Forms\Components\Select::make('bank_account_id')->relationship('bankAccount', 'account_name')->required(),
                        Forms\Components\DatePicker::make('deposit_date')->required()->default(now()),
                    ])
                    ->action(fn (ChequeReceived $record, array $data) => $record->update([
                        'status' => 'deposited',
                        'bank_account_id' => $data['bank_account_id'],
                        'deposit_date' => $data['deposit_date'],
                    ])),
                Tables\Actions\Action::make('markCleared')
                    ->label('Mark Cleared')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ChequeReceived $record) => $record->status === 'deposited' && Auth::user()->can('cheques_received.approve'))
                    ->requiresConfirmation()
                    ->action(fn (ChequeReceived $record) => $record->update(['status' => 'cleared'])),
                Tables\Actions\Action::make('markReturned')
                    ->label('Mark Returned')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ChequeReceived $record) => in_array($record->status, ['deposited', 'received']) && Auth::user()->can('cheques_received.approve'))
                    ->form([
                        Forms\Components\TextInput::make('returned_reason')->required(),
                        Forms\Components\DatePicker::make('returned_date')->required()->default(now()),
                    ])
                    ->action(fn (ChequeReceived $record, array $data) => $record->update([
                        'status' => 'returned',
                        'returned_reason' => $data['returned_reason'],
                        'returned_date' => $data['returned_date'],
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
            'index' => Pages\ListChequeReceiveds::route('/'),
            'create' => Pages\CreateChequeReceived::route('/create'),
            'edit' => Pages\EditChequeReceived::route('/{record}/edit'),
        ];
    }
}
