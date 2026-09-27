<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\Phone;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label('№')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->label('Покупатель')
                    ->description(fn (Order $record) => Phone::format($record->customer_phone))
                    ->searchable(['customer_name', 'customer_phone', 'customer_email']),
                TextColumn::make('delivery_method')
                    ->label('Получение')
                    ->toggleable(),
                TextColumn::make('total')
                    ->label('Сумма')
                    ->formatStateUsing(fn (int $state) => money_rub($state))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge(),
                TextColumn::make('paid_at')
                    ->label('Оплачен')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(OrderStatus::class)
                    ->multiple(),
                SelectFilter::make('delivery_method')
                    ->label('Получение')
                    ->options(DeliveryMethod::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
