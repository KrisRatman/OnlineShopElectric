<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Notifications\OrderStatusChanged;
use Illuminate\Support\Facades\DB;

/**
 * Смена статуса из админки: «Собирается» → «Передан в доставку» → «Выполнен».
 * Отмена идёт через CancelOrder — ей нужно вернуть резерв на склад.
 */
class ChangeOrderStatus
{
    public function __construct(private readonly CancelOrder $cancelOrder) {}

    public function handle(Order $order, OrderStatus $status, ?string $reason = null): Order
    {
        if ($status === OrderStatus::Cancelled) {
            return $this->cancelOrder->handle($order, $reason ?: 'Отменён магазином');
        }

        $order = DB::transaction(function () use ($order, $status): Order {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo($status)) {
                throw new InvalidOrderTransitionException($locked->status, $status);
            }

            $locked->update(['status' => $status]);

            return $locked;
        });

        $order->notify(new OrderStatusChanged($order));

        return $order;
    }
}
