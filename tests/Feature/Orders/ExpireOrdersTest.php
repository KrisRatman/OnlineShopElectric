<?php

use App\Actions\CancelOrder;
use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\OrderStatusChanged;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

it('отменяет неоплаченный заказ с истёкшим резервом и возвращает товар на склад', function () {
    Http::fake();
    $product = Product::factory()->stock(2)->create();
    $order = Order::factory()->withItem($product, 3)->expired()->create();

    $this->artisan('shop:expire-orders')->expectsOutputToContain('Отменено заказов: 1')->assertSuccessful();

    expect($order->fresh())
        ->status->toBe(OrderStatus::Cancelled)
        ->cancel_reason->toBe('Истёк срок оплаты');
    expect($product->fresh()->stock)->toBe(5);
    Notification::assertSentTo($order, OrderStatusChanged::class);
});

it('не трогает заказы, у которых резерв ещё действует', function () {
    $order = Order::factory()->create(['payment_due_at' => now()->addMinutes(5)]);

    $this->artisan('shop:expire-orders')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

it('перед отменой сверяется с ЮKassa: потерянное уведомление об оплате не приведёт к отмене', function () {
    $product = Product::factory()->stock(0)->create();
    $order = Order::factory()->withItem($product)->expired()->create();
    createdPayment($order, 'pay-lost');
    Http::fake(['api.yookassa.ru/v3/payments/pay-lost' => Http::response(yookassaPayment('pay-lost', 'succeeded', $order->total))]);

    $this->artisan('shop:expire-orders')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($product->fresh()->stock)->toBe(0);
});

it('если ЮKassa недоступна, заказ не отменяет — попробует в следующий раз', function () {
    $order = Order::factory()->expired()->create();
    createdPayment($order, 'pay-x');
    Http::fake(['api.yookassa.ru/*' => Http::response('', 503)]);

    $this->artisan('shop:expire-orders')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

it('возвращает резерв ровно один раз при двойной отмене', function () {
    $product = Product::factory()->stock(0)->create();
    $order = Order::factory()->withItem($product, 2)->create();

    app(CancelOrder::class)->handle($order, 'Отменён магазином');

    expect(fn () => app(CancelOrder::class)->handle($order, 'Ещё раз'))
        ->toThrow(InvalidOrderTransitionException::class);
    expect($product->fresh()->stock)->toBe(2);
});

it('планировщик не отменяет заказ, оплаченный в последний момент', function () {
    $order = Order::factory()->status(OrderStatus::Paid)->create();

    expect(fn () => app(CancelOrder::class)->handle($order, 'Истёк срок оплаты', onlyFrom: OrderStatus::PendingPayment))
        ->toThrow(InvalidOrderTransitionException::class);
    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('команда стоит в расписании', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('shop:expire-orders');
});
