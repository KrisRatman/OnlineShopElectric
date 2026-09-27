<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DeliveryMethod: string implements HasLabel
{
    case Pickup = 'pickup';
    case Courier = 'courier';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pickup => 'Самовывоз',
            self::Courier => 'Курьером',
        };
    }

    public function needsAddress(): bool
    {
        return $this === self::Courier;
    }

    /** Стоимость доставки в копейках для корзины на сумму $subtotal. */
    public function price(int $subtotal): int
    {
        if ($this === self::Pickup) {
            return 0;
        }

        return $subtotal >= config('shop.delivery.free_from') ? 0 : config('shop.delivery.courier_price');
    }
}
