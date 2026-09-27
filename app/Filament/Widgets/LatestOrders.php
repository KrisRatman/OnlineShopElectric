<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Последние заказы';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->latest('id')->limit(8))
            ->paginated(false)
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('number')->label('№')->weight('bold'),
                TextColumn::make('created_at')->label('Создан')->since(),
                TextColumn::make('customer_name')->label('Покупатель'),
                TextColumn::make('total')->label('Сумма')->formatStateUsing(fn (int $state) => money_rub($state)),
                TextColumn::make('status')->label('Статус')->badge(),
            ]);
    }
}
