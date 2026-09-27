<?php

use App\Enums\DeliveryMethod;
use App\Models\Product;
use App\Services\Cart\CartLine;
use App\Services\Cart\CartSummary;

function line(int $rubles, int $quantity, ?int $oldRubles = null, bool $unavailable = false): CartLine
{
    $product = new Product(['price' => $rubles * 100, 'old_price' => $oldRubles ? $oldRubles * 100 : null, 'stock' => 10, 'is_active' => true]);

    return new CartLine($product, $quantity, unavailable: $unavailable);
}

it('считает сумму позиций и итог в копейках', function () {
    $summary = new CartSummary([line(19990, 2), line(1490, 1)]);

    expect($summary->subtotal())->toBe(4147000)
        ->and($summary->itemsCount())->toBe(3)
        ->and($summary->total(DeliveryMethod::Pickup))->toBe(4147000);
});

it('берёт за курьера 490 ₽, пока сумма ниже порога', function () {
    $summary = new CartSummary([line(49999, 1)]);

    expect($summary->deliveryPrice(DeliveryMethod::Courier))->toBe(49000)
        ->and($summary->total(DeliveryMethod::Courier))->toBe(4999900 + 49000);
});

it('доставляет бесплатно начиная ровно с порога', function () {
    $summary = new CartSummary([line(25000, 2)]);

    expect($summary->subtotal())->toBe(5000000)
        ->and($summary->deliveryPrice(DeliveryMethod::Courier))->toBe(0);
});

it('самовывоз всегда бесплатный', function () {
    expect((new CartSummary([line(100, 1)]))->deliveryPrice(DeliveryMethod::Pickup))->toBe(0);
});

it('не считает товары, которых нет в наличии, и не даёт оформить только их', function () {
    $summary = new CartSummary([line(1000, 1), line(5000, 2, unavailable: true)]);

    expect($summary->subtotal())->toBe(100000)
        ->and($summary->itemsCount())->toBe(1)
        ->and($summary->hasUnavailable())->toBeTrue()
        ->and($summary->canCheckout())->toBeTrue();

    expect((new CartSummary([line(5000, 1, unavailable: true)]))->canCheckout())->toBeFalse();
});

it('показывает экономию по старым ценам', function () {
    $summary = new CartSummary([line(900, 2, oldRubles: 1000), line(500, 1)]);

    expect($summary->savings())->toBe(20000);
});

it('порог бесплатной доставки берётся из настроек', function () {
    config(['shop.delivery.free_from' => 1000000, 'shop.delivery.courier_price' => 30000]);

    expect(DeliveryMethod::Courier->price(999999))->toBe(30000)
        ->and(DeliveryMethod::Courier->price(1000000))->toBe(0);
});
