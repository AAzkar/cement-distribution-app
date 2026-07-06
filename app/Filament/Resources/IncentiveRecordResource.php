<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesWithPermissions;
use App\Filament\Resources\IncentiveRecordResource\Pages;
use App\Models\IncentiveRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class IncentiveRecordResource extends Resource
{
    use AuthorizesWithPermissions;

    protected static ?string $model = IncentiveRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Incentives';

    protected static ?int $navigationSort = 2;

    protected static string $permissionModule = 'incentive_records';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('sales_rep_id')
                    ->relationship('salesRep', 'name')->required()->searchable()->preload(),
                Forms\Components\Select::make('incentive_rule_id')
                    ->relationship('incentiveRule', 'name')->searchable()->preload(),
                Forms\Components\DatePicker::make('record_date')->required()->default(now()),
                Forms\Components\TextInput::make('metric_value')->required()->numeric()->default(0)->prefix('LKR '),
                Forms\Components\TextInput::make('calculated_amount')->required()->numeric()->default(0)->prefix('LKR ')->disabled()->dehydrated(),
                Forms\Components\TextInput::make('override_amount')->numeric()->prefix('LKR ')
                    ->helperText('Admin override — audited automatically'),
                Forms\Components\Textarea::make('notes')->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('salesRep.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('incentiveRule.name')->label('Rule'),
                Tables\Columns\TextColumn::make('record_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('metric_value')->money('lkr'),
                Tables\Columns\TextColumn::make('calculated_amount')->money('lkr'),
                Tables\Columns\TextColumn::make('override_amount')->money('lkr')->placeholder('—'),
                Tables\Columns\TextColumn::make('final_amount')->money('lkr')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                }),
            ])
            ->defaultSort('record_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('sales_rep_id')->relationship('salesRep', 'name')->label('Sales Rep'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (IncentiveRecord $record) => $record->status === 'pending'),
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (IncentiveRecord $record) => $record->status === 'pending' && Auth::user()->can('incentive_records.approve'))
                    ->requiresConfirmation()
                    ->action(fn (IncentiveRecord $record) => $record->update([
                        'status' => 'approved',
                        'final_amount' => $record->override_amount ?? $record->calculated_amount,
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                    ])),
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (IncentiveRecord $record) => $record->status === 'pending' && Auth::user()->can('incentive_records.approve'))
                    ->requiresConfirmation()
                    ->action(fn (IncentiveRecord $record) => $record->update([
                        'status' => 'rejected',
                        'final_amount' => 0,
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
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
            'index' => Pages\ListIncentiveRecords::route('/'),
            'create' => Pages\CreateIncentiveRecord::route('/create'),
            'edit' => Pages\EditIncentiveRecord::route('/{record}/edit'),
        ];
    }
}
