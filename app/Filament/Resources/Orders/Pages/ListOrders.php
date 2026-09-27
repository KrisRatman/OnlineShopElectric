<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        $tab = fn (string $label, OrderStatus ...$statuses) => Tab::make($label)
            ->modifyQueryUsing(fn ($query) => $query->whereIn('status', $statuses));

        return [
            'all' => Tab::make('Все'),
            'paid' => $tab('К сборке', OrderStatus::Paid)
                ->badge(fn () => OrderResource::getNavigationBadge())
                ->badgeColor('success'),
            'in_work' => $tab('В работе', OrderStatus::Processing, OrderStatus::Shipped),
            'pending' => $tab('Ждут оплаты', OrderStatus::PendingPayment),
            'completed' => $tab('Выполнены', OrderStatus::Completed),
            'cancelled' => $tab('Отменены', OrderStatus::Cancelled),
        ];
    }
}
