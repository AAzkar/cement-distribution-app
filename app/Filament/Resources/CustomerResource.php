<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesWithPermissions;
use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers\ChequesReceivedRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\SalesOrdersRelationManager;
use App\Models\Customer;
use App\Services\QrCodeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerResource extends Resource
{
    use AuthorizesWithPermissions;

    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Customers & Suppliers';

    protected static ?int $navigationSort = 1;

    protected static string $permissionModule = 'customers';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('code')->required()->maxLength(50)->unique(ignoreRecord: true),
                Forms\Components\Select::make('type')
                    ->options(['shop' => 'Shop', 'contractor' => 'Contractor', 'other' => 'Other'])
                    ->required()
                    ->default('shop'),
                Forms\Components\TextInput::make('phone')->tel(),
                Forms\Components\TextInput::make('email')->email(),
                Forms\Components\TextInput::make('address')->maxLength(255),
                Forms\Components\Select::make('zone_id')
                    ->relationship('zone', 'name')->searchable()->preload(),
                Forms\Components\Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')->searchable()->preload(),
                Forms\Components\TextInput::make('opening_balance')->required()->numeric()->default(0)->prefix('LKR '),
                Forms\Components\TextInput::make('credit_limit')->numeric()->prefix('LKR ')
                    ->helperText('Leave blank for no credit limit.'),
                Forms\Components\Toggle::make('is_active')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('code')->searchable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('zone.name')->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')->sortable(),
                Tables\Columns\TextColumn::make('opening_balance')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('outstanding_balance')
                    ->label('Outstanding')
                    ->state(fn (Customer $record) => $record->outstandingBalance())
                    ->money('lkr')
                    ->color(fn (Customer $record) => $record->outstandingBalance() > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('credit_limit')->money('lkr')->placeholder('No limit')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('zone_id')->relationship('zone', 'name')->label('Zone'),
                Tables\Filters\SelectFilter::make('warehouse_id')->relationship('warehouse', 'name')->label('Warehouse'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('statement')
                    ->label('Statement')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(fn (Customer $record) => Pages\CustomerStatement::getUrl(['record' => $record])),
                Tables\Actions\Action::make('viewQr')
                    ->label('QR Code')
                    ->icon('heroicon-o-qr-code')
                    ->color('gray')
                    ->modalHeading(fn (Customer $record) => "QR Code — {$record->name}")
                    ->modalContent(fn (Customer $record) => view('filament.customers.qr-modal', [
                        'dataUri' => app(QrCodeService::class)->dataUri($record->qrLookupUrl(), 320),
                        'url' => $record->qrLookupUrl(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Tables\Actions\Action::make('downloadQr')
                    ->label('Download QR')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn (Customer $record) => new StreamedResponse(
                        fn () => print(app(QrCodeService::class)->png($record->qrLookupUrl(), 600)),
                        200,
                        [
                            'Content-Type' => 'image/png',
                            'Content-Disposition' => "attachment; filename=\"customer-{$record->code}-qr.png\"",
                        ]
                    )),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SalesOrdersRelationManager::class,
            PaymentsRelationManager::class,
            ChequesReceivedRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
            'statement' => Pages\CustomerStatement::route('/{record}/statement'),
        ];
    }
}
