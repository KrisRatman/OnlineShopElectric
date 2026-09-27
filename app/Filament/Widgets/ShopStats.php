<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShopStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $tz = config('shop.timezone');
        $todayFrom = now($tz)->startOfDay()->utc();
        $monthFrom = now($tz)->subDays(29)->startOfDay()->utc();

        $paid = fn () => Order::query()->whereNotNull('paid_at')->where('status', '!=', OrderStatus::Cancelled);

        $todayRevenue = (int) $paid()->where('paid_at', '>=', $todayFrom)->sum('total');
        $monthOrders = $paid()->where('paid_at', '>=', $monthFrom);
        $monthRevenue = (int) (clone $monthOrders)->sum('total');
        $monthCount = (clone $monthOrders)->count();

        $toShip = Order::query()->where('status', OrderStatus::Paid)->count();
        $pending = Order::query()->where('status', OrderStatus::PendingPayment)->count();
        $lowStock = Product::query()->active()->where('stock', '<=', config('shop.low_stock'))->count();

        return [
            Stat::make('Выручка сегодня', money_rub($todayRevenue))
                ->description('Оплаченные заказы')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Выручка за 30 дней', money_rub($monthRevenue))
                ->description($monthCount ? 'Средний чек '.money_rub(intdiv($monthRevenue, $monthCount)) : 'Заказов не было')
                ->icon('heroicon-o-chart-bar'),
            Stat::make('К сборке', $toShip)
                ->description($pending ? "Ещё {$pending} ждут оплаты" : 'Неоплаченных нет')
                ->color($toShip ? 'success' : 'gray')
                ->icon('heroicon-o-archive-box'),
            Stat::make('Заканчиваются', $lowStock)
                ->description('Товаров с остатком ≤ '.config('shop.low_stock').' шт.')
                ->color($lowStock ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
