<?php

namespace App\Services\Cart;

use App\Enums\DeliveryMethod;

/**
 * Расчёт корзины: позиции, сумма товаров, доставка, итог. Всё в копейках.
 */
final readonly class CartSummary
{
    /**
     * @param  list<CartLine>  $lines
     */
    public function __construct(public array $lines) {}

    /** @return list<CartLine> */
    public function availableLines(): array
    {
        return array_values(array_filter($this->lines, fn (CartLine $line) => ! $line->unavailable));
    }

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    /** Можно ли оформить заказ: есть хотя бы одна позиция в наличии. */
    public function canCheckout(): bool
    {
        return $this->availableLines() !== [];
    }

    public function hasUnavailable(): bool
    {
        return count($this->availableLines()) !== count($this->lines);
    }

    /** Сколько штук товара (для значка в шапке). */
    public function itemsCount(): int
    {
        return array_sum(array_map(fn (CartLine $line) => $line->quantity, $this->availableLines()));
    }

    public function subtotal(): int
    {
        return array_sum(array_map(fn (CartLine $line) => $line->total(), $this->lines));
    }

    /** Экономия по сравнению со старыми ценами. */
    public function savings(): int
    {
        return array_sum(array_map(
            fn (CartLine $line) => max(0, ($line->product->old_price ?? 0) - $line->unitPrice()) * $line->quantity,
            $this->availableLines(),
        ));
    }

    public function deliveryPrice(DeliveryMethod $method): int
    {
        return $method->price($this->subtotal());
    }

    public function total(DeliveryMethod $method): int
    {
        return $this->subtotal() + $this->deliveryPrice($method);
    }
}
