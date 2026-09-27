<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

/**
 * Выручка по дням за 30 дней (по московскому времени). Дни без продаж — нули,
 * чтобы ось дат не «прыгала».
 */
class RevenueChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Выручка за 30 дней';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $tz = config('shop.timezone');
        $from = now($tz)->subDays(29)->startOfDay();

        $totals = Order::query()
            ->whereNotNull('paid_at')
            ->where('status', '!=', OrderStatus::Cancelled)
            ->where('paid_at', '>=', $from->utc())
            ->get(['paid_at', 'total'])
            ->groupBy(fn (Order $order) => $order->paid_at->timezone($tz)->toDateString())
            ->map(fn ($orders) => $orders->sum('total'));

        $labels = [];
        $values = [];

        for ($day = $from; $day->lte(now($tz)); $day = $day->addDay()) {
            $labels[] = $day->isoFormat('D MMM');
            $values[] = intdiv((int) ($totals[$day->toDateString()] ?? 0), 100);
        }

        return [
            'datasets' => [[
                'label' => 'Выручка, ₽',
                'data' => $values,
                'borderColor' => '#1f47f5',
                'backgroundColor' => 'rgba(31, 71, 245, 0.12)',
                'fill' => true,
                'cubicInterpolationMode' => 'monotone',
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
