<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\Payment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Платежи заказа — только просмотр: статусы меняет ЮKassa.
 */
class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Платежи ЮKassa';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('Платежей пока нет')
            ->columns([
                TextColumn::make('provider_payment_id')
                    ->label('ID в ЮKassa')
                    ->placeholder('не создан')
                    ->copyable()
                    ->fontFamily('mono'),
                TextColumn::make('amount')
                    ->label('Сумма')
                    ->formatStateUsing(fn (int $state) => money_rub($state)),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->description(fn (Payment $record) => $record->cancellationReasonLabel()),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i:s'),
                TextColumn::make('paid_at')
                    ->label('Оплачен')
                    ->dateTime('d.m.Y H:i:s')
                    ->placeholder('—'),
            ]);
    }
}
