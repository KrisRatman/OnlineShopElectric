<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Services\Payments\PaymentService;
use App\Services\YooKassa\DemoYooKassaClient;
use App\Services\YooKassa\YooKassaClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    config(['yookassa.demo' => true]);
    app()->forgetInstance(YooKassaClient::class);
    Http::fake();
    Notification::fake();
});

it('в демо-режиме оплата проходит через тот же поток, без запросов в ЮKassa', function () {
    $product = Product::factory()->price(79990)->stock(3)->create();
    $order = Order::factory()->withItem($product)->create();

    $payment = app(PaymentService::class)->start($order);
    expect(app(YooKassaClient::class))->toBeInstanceOf(DemoYooKassaClient::class)
        ->and($payment->confirmation_url)->toBe(route('demo-payment.show', $payment->provider_payment_id));

    $this->get($payment->confirmation_url)->assertOk()->assertSee('79 990 ₽')->assertSee('Демо-режим');

    $this->post(route('demo-payment.complete', $payment->provider_payment_id), ['result' => 'success'])
        ->assertRedirect(route('orders.payment-return', $order->token));

    $this->get(route('orders.payment-return', $order->token));

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Succeeded);
    Http::assertNothingSent();
});

it('отклонённый демо-платёж оставляет заказ ждать оплаты, повторная попытка создаёт новый', function () {
    $order = Order::factory()->create();
    $first = app(PaymentService::class)->start($order);

    $this->post(route('demo-payment.complete', $first->provider_payment_id), ['result' => 'fail']);
    $this->get(route('orders.payment-return', $order->token));

    expect($first->fresh()->status)->toBe(PaymentStatus::Canceled)
        ->and($order->fresh()->status)->toBe(OrderStatus::PendingPayment);

    $second = app(PaymentService::class)->start($order->fresh());
    expect($second->id)->not->toBe($first->id);
});

it('повтор с тем же ключом идемпотентности возвращает тот же демо-платёж', function () {
    $client = new DemoYooKassaClient;
    $payload = ['amount' => ['value' => '100.00', 'currency' => 'RUB'], 'confirmation' => ['return_url' => 'http://localhost/x']];

    expect($client->createPayment($payload, 'key-1')->id)->toBe($client->createPayment($payload, 'key-1')->id)
        ->and($client->createPayment($payload, 'key-2')->id)->not->toBe($client->createPayment($payload, 'key-1')->id);
});

it('без демо-режима страница демо-оплаты недоступна', function () {
    config(['yookassa.demo' => false]);

    $this->get('/demo-payment/demo-123')->assertNotFound();
});
