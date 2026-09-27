<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\OrderStatusChanged;
use Illuminate\Support\Facades\DB;

/**
 * Отмена заказа: статус «Отменён» и возврат резерва на склад.
 *
 * Статус перечитывается под блокировкой, поэтому одновременная отмена (админ +
 * планировщик) вернёт товар на склад ровно один раз.
 */
class CancelOrder
{
    /**
     * @param  OrderStatus|null  $onlyFrom  отменять, только если заказ всё ещё в этом статусе
     *                                      (планировщик не должен отменить заказ, оплаченный секунду назад)
     */
    public function handle(Order $order, string $reason, bool $notify = true, ?OrderStatus $onlyFrom = null): Order
    {
        $order = DB::transaction(function () use ($order, $reason, $onlyFrom): Order {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (($onlyFrom !== null && $locked->status !== $onlyFrom) || ! $locked->status->canTransitionTo(OrderStatus::Cancelled)) {
                throw new InvalidOrderTransitionException($locked->status, OrderStatus::Cancelled);
            }

            self::returnStock($locked);

            $locked->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);

            return $locked;
        });

        if ($notify) {
            $order->notify(new OrderStatusChanged($order));
        }

        return $order;
    }

    /** Вернуть товары заказа на склад. Вызывать внутри транзакции. */
    public static function returnStock(Order $order): void
    {
        $quantities = $order->items()->whereNotNull('product_id')->pluck('quantity', 'product_id');

        $products = Product::query()->whereKey($quantities->keys())->orderBy('id')->lockForUpdate()->get();

        foreach ($products as $product) {
            $product->increment('stock', $quantities[$product->id]);
        }
    }
}
