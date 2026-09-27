<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderPlaced;
use App\Services\Cart\CartException;
use Illuminate\Support\Facades\DB;

/**
 * Оформление заказа с резервом товара.
 *
 * Всё в одной транзакции: блокируем строки товаров (lockForUpdate), проверяем
 * остаток, фиксируем цены в позициях заказа и уменьшаем остаток. Два покупателя,
 * которые одновременно берут последний ноутбук, выстраиваются в очередь на блокировке:
 * второй увидит уже уменьшенный остаток и получит отказ.
 */
class PlaceOrder
{
    public function handle(Cart $cart, CheckoutData $data, ?User $user = null): Order
    {
        $order = DB::transaction(function () use ($cart, $data, $user): Order {
            $items = $cart->items()->get()->keyBy('product_id');

            if ($items->isEmpty()) {
                throw new CartException('Корзина пуста.');
            }

            // Блокируем в порядке id — так параллельные заказы не упрутся друг в друга (deadlock).
            $products = Product::query()
                ->whereKey($items->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $lines = [];
            $problems = [];

            foreach ($products as $product) {
                $quantity = $items[$product->id]->quantity;

                if (! $product->is_active || $product->stock < 1) {
                    $problems[] = "«{$product->name}» — нет в наличии";
                } elseif ($product->stock < $quantity) {
                    $problems[] = "«{$product->name}» — осталось {$product->stock} шт.";
                } else {
                    $lines[] = [$product, $quantity];
                }
            }

            if ($problems !== []) {
                throw new CartException('Пока вы оформляли заказ, изменилось наличие: '.implode('; ', $problems).'.');
            }

            $subtotal = array_sum(array_map(fn (array $line) => $line[0]->price * $line[1], $lines));
            $deliveryPrice = $data->delivery->price($subtotal);

            $order = Order::query()->create([
                'user_id' => $user?->id,
                'status' => OrderStatus::PendingPayment,
                'customer_name' => $data->name,
                'customer_email' => $data->email,
                'customer_phone' => $data->phone,
                'delivery_method' => $data->delivery,
                'delivery_address' => $data->delivery->needsAddress() ? $data->address : null,
                'comment' => $data->comment,
                'subtotal' => $subtotal,
                'delivery_price' => $deliveryPrice,
                'total' => $subtotal + $deliveryPrice,
                'payment_due_at' => now()->addMinutes((int) config('shop.payment_ttl')),
            ]);

            foreach ($lines as [$product, $quantity]) {
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'price' => $product->price,
                    'quantity' => $quantity,
                    'total' => $product->price * $quantity,
                ]);

                // Резерв: остаток уменьшаем сразу, вернём при отмене неоплаченного заказа.
                $product->decrement('stock', $quantity);
            }

            $cart->items()->delete();

            return $order;
        });

        $order->notify(new OrderPlaced($order));

        return $order;
    }
}
