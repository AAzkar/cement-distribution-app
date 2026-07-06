<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesWithPermissions;
use App\Models\CashbookEntry;
use App\Models\VoucherSequence;
use App\Services\CashbookVoucherReceiptService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

abstract class BaseCashbookResource extends Resource
{
    use AuthorizesWithPermissions;

    protected static ?string $model = CashbookEntry::class;

    protected static string $permissionModule = 'cashbook';

    protected static ?string $navigationGroup = 'Cashbook';

    abstract public static function direction(): string;

    abstract public static function subtypeOptions(): array;

    public static function voucherSequenceKey(): string
    {
        return static::direction();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('direction', static::direction());
    }

    public static function canEdit(Model $record): bool
    {
        if ($record->isLocked()) {
            return false;
        }

        return parent::canEdit($record);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Entry Details')->schema([
                Forms\Components\DatePicker::make('entry_date')->required()->default(now()),
                Forms\Components\Select::make('subtype')
                    ->options(static::subtypeOptions())
                    ->required()
                    ->live(),
                Forms\Components\Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')->required()->searchable()->preload(),
                Forms\Components\Select::make('zone_id')
                    ->relationship('zone', 'name')->searchable()->preload(),
                Forms\Components\TextInput::make('amount')->numeric()->required()->prefix('LKR '),
                Forms\Components\Select::make('payment_mode_id')
                    ->relationship('paymentMode', 'name')->required()->searchable()->preload(),
                Forms\Components\TextInput::make('reference')->maxLength(255)
                    ->helperText('Invoice / receipt / cheque no'),
                Forms\Components\Textarea::make('description')->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Linked Records')->schema([
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')->searchable()->preload()
                    ->visible(fn (Get $get) => in_array($get('subtype'), ['cheque_received', 'sales_rep_collection'])),
                Forms\Components\Select::make('sales_rep_id')
                    ->relationship('salesRep', 'name')->searchable()->preload()
                    ->visible(fn (Get $get) => $get('subtype') === 'sales_rep_collection'),
                Forms\Components\Select::make('supplier_id')
                    ->relationship('supplier', 'name')->searchable()->preload()
                    ->visible(fn (Get $get) => $get('subtype') === 'supplier_payment'),
                Forms\Components\Select::make('expense_category_id')
                    ->relationship('expenseCategory', 'name')->searchable()->preload()
                    ->visible(fn (Get $get) => $get('subtype') === 'expense'),
                Forms\Components\Select::make('bank_account_id')
                    ->relationship('bankAccount', 'account_name')->searchable()->preload()
                    ->visible(fn (Get $get) => in_array($get('subtype'), ['bank_transfer', 'cheque_received', 'cheque_issued'])),
                Forms\Components\Select::make('cheque_received_id')
                    ->relationship('chequeReceived', 'cheque_no')->searchable()->preload()
                    ->visible(fn (Get $get) => $get('subtype') === 'cheque_received'),
                Forms\Components\Select::make('cheque_issued_id')
                    ->relationship('chequeIssued', 'cheque_no')->searchable()->preload()
                    ->visible(fn (Get $get) => $get('subtype') === 'cheque_issued'),
            ])->columns(2),

            Forms\Components\Section::make('Attachment')->schema([
                Forms\Components\SpatieMediaLibraryFileUpload::make('attachments')
                    ->collection('attachments')
                    ->multiple()
                    ->acceptedFileTypes(['image/*', 'application/pdf'])
                    ->openable(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('voucher_no')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('entry_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('subtype')->badge(),
                Tables\Columns\TextColumn::make('warehouse.name')->sortable(),
                Tables\Columns\TextColumn::make('amount')->money('lkr')->sortable(),
                Tables\Columns\TextColumn::make('paymentMode.name'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'draft' => 'gray',
                    'pending_approval' => 'warning',
                    'approved' => 'success',
                    'locked' => 'danger',
                }),
                Tables\Columns\TextColumn::make('createdBy.name')->label('Created By')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('entry_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')->relationship('warehouse', 'name'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'pending_approval' => 'Pending Approval',
                    'approved' => 'Approved',
                    'locked' => 'Locked',
                ]),
                Tables\Filters\Filter::make('entry_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('entry_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('entry_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('printVoucher')
                    ->label('Print Voucher')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->action(fn (CashbookEntry $record) => app(CashbookVoucherReceiptService::class)->toPdf($record)),
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CashbookEntry $record) => $record->status !== 'approved' && $record->status !== 'locked' && Auth::user()->can('cashbook.approve'))
                    ->requiresConfirmation()
                    ->action(fn (CashbookEntry $record) => $record->update([
                        'status' => 'approved',
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                    ])),
                Tables\Actions\Action::make('lock')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (CashbookEntry $record) => $record->status === 'approved' && Auth::user()->can('cashbook.approve'))
                    ->requiresConfirmation()
                    ->action(fn (CashbookEntry $record) => $record->update([
                        'status' => 'locked',
                        'locked_at' => now(),
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function mutateFormDataBeforeCreateHook(array $data): array
    {
        $data['direction'] = static::direction();
        $data['voucher_no'] = VoucherSequence::next(static::voucherSequenceKey());
        $data['created_by'] = Auth::id();
        $data['status'] = 'draft';

        return $data;
    }
}
