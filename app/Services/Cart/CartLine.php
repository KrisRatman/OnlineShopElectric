<?php

namespace App\Services\Cart;

use App\Models\Product;

/**
 * Строка корзины с посчитанной суммой. Цена — текущая цена товара.
 */
final readonly class CartLine
{
    public function __construct(
        public Product $product,
        public int $quantity,
        // Товар снят с продажи или закончился: в сумму и заказ не попадает.
        public bool $unavailable = false,
        // Почему количество изменилось, например «на складе осталось 2 шт.».
        public ?string $notice = null,
    ) {}

    public function unitPrice(): int
    {
        return $this->product->price;
    }

    public function total(): int
    {
        return $this->unavailable ? 0 : $this->unitPrice() * $this->quantity;
    }

    /** Сколько можно положить в корзину: не больше остатка и лимита на один товар. */
    public function maxQuantity(): int
    {
        return CartService::maxQuantityFor($this->product);
    }
}
