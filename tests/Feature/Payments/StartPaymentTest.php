<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('создаёт платёж в ЮKassa: сумма строкой, авторизация, ключ идемпотентности, адрес возврата', function () {
    Http::fake(['api.yookassa.ru/*' => Http::response(yookassaPayment('pay-42', 'pending', 1049000))]);
    $order = Order::factory()->create(['total' => 1049000]);

    $payment = app(PaymentService::class)->start($order);

    expect($payment)
        ->provider_payment_id->toBe('pay-42')
        ->status->toBe(PaymentStatus::Pending)
        ->amount->toBe(1049000)
        ->confirmation_url->toContain('yoomoney.ru');

    Http::assertSent(function (Request $request) use ($order, $payment) {
        return $request->url() === 'https://api.yookassa.ru/v3/payments'
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('123456:test_secret'))
            && $request->hasHeader('Idempotence-Key', $payment->idempotence_key)
            && $request['amount'] === ['value' => '10490.00', 'currency' => 'RUB']
            && $request['capture'] === true
            && $request['confirmation']['return_url'] === route('orders.payment-return', $order->token)
            && $request['metadata']['order_id'] === $order->id;
    });
});

it('после сбоя повторяет запрос с тем же ключом идемпотентности — второй платёж не появится', function () {
    Http::fakeSequence('api.yookassa.ru/*')
        ->push(['type' => 'error', 'description' => 'Сервис недоступен'], 503)
        ->push(['type' => 'error', 'description' => 'Сервис недоступен'], 503)
        ->push(['type' => 'error', 'description' => 'Сервис недоступен'], 503)
        ->push(yookassaPayment('pay-1', 'pending', 5000000));
    $order = Order::factory()->create();

    expect(fn () => app(PaymentService::class)->start($order))->toThrow(PaymentException::class);
    $payment = app(PaymentService::class)->start($order);

    expect($order->payments()->count())->toBe(1)
        ->and($payment->provider_payment_id)->toBe('pay-1');

    $keys = collect(Http::recorded())->map(fn ($pair) => $pair[0]->header('Idempotence-Key')[0])->unique();
    expect($keys)->toHaveCount(1);
});

it('повторное «Оплатить» ведёт на тот же платёж, если он ещё живой', function () {
    $order = Order::factory()->create();
    $existing = createdPayment($order, 'pay-live');
    Http::fake(['api.yookassa.ru/v3/payments/pay-live' => Http::response(yookassaPayment('pay-live', 'pending', $order->total))]);

    $payment = app(PaymentService::class)->start($order);

    expect($payment->id)->toBe($existing->id)
        ->and($order->payments()->count())->toBe(1);
    Http::assertNotSent(fn (Request $request) => $request->method() === 'POST');
});

it('если прошлый платёж отменён (истекла ссылка), создаёт новый', function () {
    $order = Order::factory()->create();
    createdPayment($order, 'pay-old');
    Http::fake([
        'api.yookassa.ru/v3/payments/pay-old' => Http::response(yookassaPayment('pay-old', 'canceled', $order->total)),
        'api.yookassa.ru/v3/payments' => Http::response(yookassaPayment('pay-new', 'pending', $order->total)),
    ]);

    $payment = app(PaymentService::class)->start($order);

    expect($payment->provider_payment_id)->toBe('pay-new')
        ->and($order->payments()->pluck('status', 'provider_payment_id')->map->value->all())
        ->toBe(['pay-old' => 'canceled', 'pay-new' => 'pending']);
});

it('не создаёт платёж для заказа, который не ждёт оплаты', function (OrderStatus $status) {
    Http::fake();
    $order = Order::factory()->status($status)->create();

    expect(fn () => app(PaymentService::class)->start($order))->toThrow(PaymentException::class);
    Http::assertNothingSent();
})->with([OrderStatus::Paid, OrderStatus::Cancelled, OrderStatus::Completed]);

it('передаёт чек 54-ФЗ: позиции и доставка в сумме дают сумму платежа', function () {
    config(['yookassa.receipt.enabled' => true, 'yookassa.receipt.vat_code' => 4]);
    Http::fake(['api.yookassa.ru/*' => Http::response(yookassaPayment('pay-r', 'pending', 0))]);
    $product = Product::factory()->price(1990)->create(['name' => 'Чехол для iPhone']);
    $order = Order::factory()->withItem($product, 3)->create(['customer_email' => 'buyer@example.com', 'delivery_price' => 49000]);
    $order->update(['total' => $order->subtotal + 49000]);

    app(PaymentService::class)->start($order->fresh());

    Http::assertSent(function (Request $request) use ($order) {
        $receipt = $request['receipt'];
        $sum = collect($receipt['items'])->sum(fn ($item) => (int) round((float) $item['quantity']) * (int) str_replace('.', '', $item['amount']['value']));

        return $receipt['customer']['email'] === 'buyer@example.com'
            && $receipt['items'][0]['quantity'] === '3.00'
            && $receipt['items'][0]['amount']['value'] === '1990.00'
            && $receipt['items'][0]['vat_code'] === 4
            && $receipt['items'][1]['payment_subject'] === 'service'
            && $sum === $order->total;
    });
});
