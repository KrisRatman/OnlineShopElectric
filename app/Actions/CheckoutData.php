<?php

namespace App\Actions;

use App\Enums\DeliveryMethod;

final readonly class CheckoutData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $phone,
        public DeliveryMethod $delivery,
        public ?string $address = null,
        public ?string $comment = null,
    ) {}
}
