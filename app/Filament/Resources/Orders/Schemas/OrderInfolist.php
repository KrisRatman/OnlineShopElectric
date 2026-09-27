<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\Phone;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Состав заказа')
                    ->columnSpan(2)
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->columns(4)
                            ->schema([
                                TextEntry::make('product_name')
                                    ->label('Товар')
                                    ->columnSpan(2)
                                    ->helperText(fn ($record) => 'Артикул '.$record->product_sku),
                                TextEntry::make('quantity')
                                    ->label('Кол-во')
                                    ->formatStateUsing(fn ($state, $record) => $state.' × '.money_rub($record->price)),
                                TextEntry::make('total')
                                    ->label('Сумма')
                                    ->formatStateUsing(fn (int $state) => money_rub($state))
                                    ->weight('bold'),
                            ]),
                        TextEntry::make('totals')
                            ->hiddenLabel()
                            ->state(fn (Order $record) => 'Товары: '.money_rub($record->subtotal)
                                .' · Доставка: '.($record->delivery_price ? money_rub($record->delivery_price) : 'бесплатно')
                                .' · Итого: '.money_rub($record->total))
                            ->weight('bold')
                            ->alignEnd(),
                    ]),

                Section::make('Заказ')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('status')->label('Статус')->badge(),
                        TextEntry::make('created_at')->label('Создан')->dateTime('d.m.Y H:i'),
                        TextEntry::make('payment_due_at')
                            ->label('Резерв до')
                            ->dateTime('d.m.Y H:i')
                            ->visible(fn (Order $record) => $record->status === OrderStatus::PendingPayment),
                        TextEntry::make('paid_at')->label('Оплачен')->dateTime('d.m.Y H:i')->placeholder('—'),
                        TextEntry::make('cancel_reason')
                            ->label('Причина отмены')
                            ->visible(fn (Order $record) => $record->status === OrderStatus::Cancelled),
                        TextEntry::make('link')
                            ->label('Страница заказа')
                            ->state('Открыть на витрине')
                            ->url(fn (Order $record) => route('orders.show', $record->token), shouldOpenInNewTab: true)
                            ->color('primary'),
                    ]),

                Section::make('Покупатель и доставка')
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('customer_name')
                            ->label('Имя')
                            ->helperText(fn (Order $record) => $record->user_id ? 'Зарегистрирован' : 'Гость'),
                        TextEntry::make('customer_phone')
                            ->label('Телефон')
                            ->formatStateUsing(fn (string $state) => Phone::format($state))
                            ->url(fn (Order $record) => 'tel:'.$record->customer_phone)
                            ->copyable(),
                        TextEntry::make('customer_email')->label('Email')->copyable(),
                        TextEntry::make('delivery_method')
                            ->label('Получение')
                            ->helperText(fn (Order $record) => $record->delivery_address ?? config('shop.delivery.pickup_address')),
                        TextEntry::make('comment')
                            ->label('Комментарий')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
