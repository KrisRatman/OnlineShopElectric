<?php

namespace Database\Factories;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'status' => OrderStatus::PendingPayment,
            'customer_name' => fake()->name(),
            'customer_email' => fake()->unique()->safeEmail(),
            'customer_phone' => '+79161234567',
            'delivery_method' => DeliveryMethod::Pickup,
            'subtotal' => 5000000,
            'delivery_price' => 0,
            'total' => 5000000,
            'payment_due_at' => now()->addHour(),
        ];
    }

    /**
     * Заказ с позицией: товар уже зарезервирован (остаток не трогаем — как после PlaceOrder).
     */
    public function withItem(Product $product, int $quantity = 1): static
    {
        return $this->state(fn () => [
            'subtotal' => $product->price * $quantity,
            'total' => $product->price * $quantity,
        ])->afterCreating(fn (Order $order) => $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'price' => $product->price,
            'quantity' => $quantity,
            'total' => $product->price * $quantity,
        ]));
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'paid_at' => $status->isPaid() ? now() : null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['payment_due_at' => now()->subMinute()]);
    }
}
