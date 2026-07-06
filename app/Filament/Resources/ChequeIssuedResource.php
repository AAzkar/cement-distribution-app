<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesWithPermissions;
use App\Filament\Resources\ChequeIssuedResource\Pages;
use App\Models\ChequeIssued;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ChequeIssuedResource extends Resource
{
    use AuthorizesWithPermissions;

    protected static ?string $model = ChequeIssued::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-minus';

    protected static ?string $navigationGroup = 'Cheque Management';

    protected static ?int $navigationSort = 2;

    protected static string $permissionModule = 'cheques_issued';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('supplier_id')
                    ->relationship('supplier', 'name')->searchable()->preload(),
                Forms\Components\TextInput::make('payee_name')
                    ->helperText('Use if the payee is not a registered supplier'),
                Forms\Components\TextInput::make('bank_name')->required()->maxLength(255),
                Forms\Components\TextInput::make('cheque_no')->required()->maxLength(255),
                Forms\Components\TextInput::make('amount')->numeric()->required()->prefix('LKR '),
                Forms\Components\DatePicker::make('issue_date')->required()->default(now()),
                Forms\Components\Select::make('bank_account_id')
                    ->relationship('bankAccount', 'account_name')->required()->searchable()->preload(),
                Forms\Components\Select::make('status')
                    ->options([
                        'issued' => 'Issued',
                        'cleared' => 'Cleared',
                        'bounced' => 'Bounced',
                    ])
                    ->default('issued')
                    ->required(),
                Forms\Components\DatePicker::make('cleared_date')
                    ->visible(fn (Forms\Get $get) => $get('status') === 'cleared'),
                Forms\Components\TextInput::make('bounced_reason')
                    ->visible(fn (Forms\Get $get) => $get('status') === 'bounced'),
                Forms\Components\Textarea::make('notes')->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier.name')->default('—')->searchable(),
                Tables\Columns\TextColumn::make('payee_name')->searchable(),
                Tables\Columns\TextColumn::make('bank_name')->searchable(),
                Tables\Columns\TextColumn::make('cheque_no')->searchable(),
                Tables\Columns\TextColumn::make('amount')->money('lkr')->sortable(),
                Tables\Columns\TextColumn::make('issue_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'issued' => 'warning',
                    'cleared' => 'success',
                    'bounced' => 'danger',
                }),
            ])
            ->defaultSort('issue_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'issued' => 'Issued',
                    'cleared' => 'Cleared',
                    'bounced' => 'Bounced',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('markCleared')
                    ->label('Mark Cleared')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ChequeIssued $record) => $record->status === 'issued' && Auth::user()->can('cheques_issued.approve'))
                    ->form([Forms\Components\DatePicker::make('cleared_date')->required()->default(now())])
                    ->action(fn (ChequeIssued $record, array $data) => $record->update([
                        'status' => 'cleared',
                        'cleared_date' => $data['cleared_date'],
                    ])),
                Tables\Actions\Action::make('markBounced')
                    ->label('Mark Bounced')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ChequeIssued $record) => $record->status === 'issued' && Auth::user()->can('cheques_issued.approve'))
                    ->form([Forms\Components\TextInput::make('bounced_reason')->required()])
                    ->action(fn (ChequeIssued $record, array $data) => $record->update([
                        'status' => 'bounced',
                        'bounced_reason' => $data['bounced_reason'],
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
            'index' => Pages\ListChequeIssueds::route('/'),
            'create' => Pages\CreateChequeIssued::route('/create'),
            'edit' => Pages\EditChequeIssued::route('/{record}/edit'),
        ];
    }
}
