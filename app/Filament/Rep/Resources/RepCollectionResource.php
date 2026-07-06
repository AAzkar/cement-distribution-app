<?php

namespace App\Filament\Rep\Resources;

use App\Filament\Rep\Concerns\ScopedToCurrentRep;
use App\Filament\Rep\Resources\RepCollectionResource\Pages;
use App\Models\ChequeReceived;
use App\Models\RepCollection;
use App\Services\RepCollectionReceiptService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RepCollectionResource extends Resource
{
    use ScopedToCurrentRep;

    protected static ?string $model = RepCollection::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'My Collections';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        $rep = Auth::user();

        return $form
            ->schema([
                Forms\Components\Select::make('warehouse_id')
                    ->relationship('warehouse', 'name', fn ($query) => $query->whereIn('id', $rep->warehouses()->pluck('warehouses.id')))
                    ->required()
                    ->default(fn () => $rep->warehouses()->first()?->id),
                Forms\Components\Select::make('zone_id')
                    ->relationship('zone', 'name', fn ($query) => $query->whereIn('id', $rep->zones()->pluck('zones.id')))
                    ->default(fn () => $rep->zones()->first()?->id),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')->searchable()->preload()->required()
                    ->default(fn () => request()->query('customer_id')),
                Forms\Components\DatePicker::make('entry_date')->required()->default(now()),
                Forms\Components\Select::make('mode')
                    ->options(['cash' => 'Cash', 'cheque' => 'Cheque', 'bank_transfer' => 'Bank Transfer'])
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('amount')->required()->numeric()->prefix('LKR '),
                Forms\Components\TextInput::make('reference')->visible(fn (Forms\Get $get) => $get('mode') !== 'cash'),
                Forms\Components\Section::make('Cheque Details')
                    ->visible(fn (Forms\Get $get) => $get('mode') === 'cheque')
                    ->schema([
                        Forms\Components\TextInput::make('cheque_bank_name')->label('Bank Name')->dehydrated(false),
                        Forms\Components\TextInput::make('cheque_no')->label('Cheque No')->dehydrated(false),
                        Forms\Components\FileUpload::make('cheque_photo')
                            ->label('Cheque Photo')
                            ->image()
                            ->imageEditor()
                            ->directory('cheque-photos')
                            ->dehydrated(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('entry_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('customer.name'),
                Tables\Columns\TextColumn::make('mode')->badge(),
                Tables\Columns\TextColumn::make('amount')->money('lkr'),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->defaultSort('entry_date', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (RepCollection $record) => $record->status === 'pending'),
                Tables\Actions\Action::make('printReceipt')
                    ->label('Print Receipt')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->action(fn (RepCollection $record) => app(RepCollectionReceiptService::class)->toPdf($record)),
            ]);
    }

    public static function mutateFormDataBeforeCreateHook(array $data): array
    {
        $data['sales_rep_id'] = Auth::id();
        $data['status'] = 'pending';

        if (($data['mode'] ?? null) === 'cheque' && ! empty($data['cheque_no'])) {
            $cheque = ChequeReceived::create([
                'customer_id' => $data['customer_id'],
                'sales_rep_id' => Auth::id(),
                'bank_name' => $data['cheque_bank_name'] ?? 'Unknown',
                'cheque_no' => $data['cheque_no'],
                'amount' => $data['amount'],
                'received_date' => $data['entry_date'],
                'status' => 'received',
            ]);

            if (! empty($data['cheque_photo'])) {
                $path = is_array($data['cheque_photo']) ? reset($data['cheque_photo']) : $data['cheque_photo'];
                $cheque->addMediaFromDisk($path, 'public')->toMediaCollection('attachments');
            }

            $data['cheque_received_id'] = $cheque->id;
        }

        unset($data['cheque_bank_name'], $data['cheque_no'], $data['cheque_photo']);

        return $data;
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
