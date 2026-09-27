<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Admin\NewPaidOrder;
use App\Notifications\Admin\PaymentNeedsAttention;
use App\Notifications\OrderPaid;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->admin = User::factory()->admin()->create();
    $this->product = Product::factory()->price(79990)->stock(4)->create();
    $this->order = Order::factory()->withItem($this->product)->create();
    $this->payment = createdPayment($this->order, 'pay-100');
});

/** ЮKassa отвечает на GET /payments/pay-100 указанным статусом. */
function apiSays(string $status, ?int $amount = null): void
{
    Http::fake(['api.yookassa.ru/v3/payments/pay-100' => Http::response(
        yookassaPayment('pay-100', $status, $amount ?? test()->order->total),
    )]);
}

it('успешная оплата: платёж и заказ оплачены, покупателю и админу уходят письма', function () {
    apiSays('succeeded');

    sendWebhook('payment.succeeded', yookassaPayment('pay-100', 'succeeded', $this->order->total))->assertOk();

    expect($this->payment->fresh())
        ->status->toBe(PaymentStatus::Succeeded)
        ->paid_at->not->toBeNull();
    expect($this->order->fresh())
        ->status->toBe(OrderStatus::Paid)
        ->paid_at->not->toBeNull();

    Notification::assertSentTo($this->order, OrderPaid::class);
    Notification::assertSentTo($this->admin, NewPaidOrder::class);
});

it('повторное уведомление ничего не меняет и не шлёт писем второй раз', function () {
    apiSays('succeeded');
    $body = yookassaPayment('pay-100', 'succeeded', $this->order->total);

    sendWebhook('payment.succeeded', $body)->assertOk();
    $paidAt = $this->order->fresh()->paid_at;
    $paymentPaidAt = $this->payment->fresh()->paid_at;

    $this->travel(5)->minutes();
    sendWebhook('payment.succeeded', $body)->assertOk();
    sendWebhook('payment.succeeded', $body)->assertOk();

    expect($this->order->fresh())
        ->status->toBe(OrderStatus::Paid)
        ->paid_at->toEqual($paidAt);
    expect($this->payment->fresh()->paid_at)->toEqual($paymentPaidAt)
        ->and($this->product->fresh()->stock)->toBe(4);

    Notification::assertSentToTimes($this->order, OrderPaid::class, 1);
    Notification::assertSentToTimes($this->admin, NewPaidOrder::class, 1);
    // Повтор того же уведомления — не «вторая оплата»: админа не тревожим.
    Notification::assertNotSentTo($this->admin, PaymentNeedsAttention::class);
});

it('опоздавшее уведомление о старом статусе не откатывает оплату', function () {
    apiSays('succeeded');
    sendWebhook('payment.succeeded', ['id' => 'pay-100'])->assertOk();

    // ЮKassa прислала запоздавшее «ожидает оплаты» — а API и так скажет правду.
    sendWebhook('payment.waiting_for_capture', ['id' => 'pay-100'])->assertOk();

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Succeeded)
        ->and($this->order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('телу уведомления не верит: если API говорит «ещё не оплачено», заказ не оплачен', function () {
    apiSays('pending');

    sendWebhook('payment.succeeded', yookassaPayment('pay-100', 'succeeded', $this->order->total))->assertOk();

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($this->order->fresh()->status)->toBe(OrderStatus::PendingPayment);
    Notification::assertNothingSent();
});

it('отменённый платёж: заказ ждёт новой попытки, резерв на месте', function () {
    apiSays('canceled');

    sendWebhook('payment.canceled', ['id' => 'pay-100'])->assertOk();

    expect($this->payment->fresh())
        ->status->toBe(PaymentStatus::Canceled)
        ->cancellation_reason->toBe('expired_on_confirmation');
    expect($this->order->fresh()->status)->toBe(OrderStatus::PendingPayment)
        ->and($this->product->fresh()->stock)->toBe(4);
});

it('сумма не совпала — заказ не оплачивается, админ получает предупреждение', function () {
    apiSays('succeeded', amount: 100);

    sendWebhook('payment.succeeded', ['id' => 'pay-100'])->assertOk();

    expect($this->order->fresh()->status)->toBe(OrderStatus::PendingPayment)
        ->and($this->payment->fresh()->status)->toBe(PaymentStatus::Pending);
    Notification::assertSentTo($this->admin, PaymentNeedsAttention::class);
});

it('уведомление о неизвестном платеже принимает, но ничего не делает', function () {
    Http::fake();

    sendWebhook('payment.succeeded', ['id' => 'someone-else'])->assertOk();

    Http::assertNothingSent();
    expect($this->order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

it('отклоняет уведомления не с адресов ЮKassa', function () {
    Http::fake();

    sendWebhook('payment.succeeded', ['id' => 'pay-100'], ip: '8.8.8.8')->assertForbidden();

    Http::assertNothingSent();
    expect($this->order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

it('проверку IP можно выключить (сайт за прокси), статус всё равно берётся из API', function () {
    config(['yookassa.verify_ip' => false]);
    apiSays('succeeded');

    sendWebhook('payment.succeeded', ['id' => 'pay-100'], ip: '104.16.0.1')->assertOk();

    expect($this->order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('если API ЮKassa недоступно, отвечает 500 — ЮKassa повторит уведомление позже', function () {
    Http::fake(['api.yookassa.ru/*' => Http::response('', 503)]);

    sendWebhook('payment.succeeded', ['id' => 'pay-100'])->assertStatus(500);

    expect($this->order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

it('отвечает 400 на запрос без id платежа', function () {
    sendWebhook('payment.succeeded', [])->assertStatus(400);
});

it('не требует CSRF-токена', function () {
    apiSays('succeeded');

    $this->withServerVariables(['REMOTE_ADDR' => YOOKASSA_IP])
        ->post('/payments/yookassa/webhook', ['type' => 'notification', 'event' => 'payment.succeeded', 'object' => ['id' => 'pay-100']])
        ->assertOk();
});

it('оплата заказа, отменённого по таймауту, восстанавливает его, если товар ещё есть', function () {
    $this->order->update(['status' => OrderStatus::Cancelled, 'cancelled_at' => now(), 'cancel_reason' => 'Истёк срок оплаты']);
    apiSays('succeeded');

    sendWebhook('payment.succeeded', ['id' => 'pay-100'])->assertOk();

    expect($this->order->fresh())
        ->status->toBe(OrderStatus::Paid)
        ->cancel_reason->toBeNull();
    expect($this->product->fresh()->stock)->toBe(3);
    Notification::assertSentTo($this->order, OrderPaid::class);
});

it('оплата отменённого заказа без товара на складе — сигнал админу про возврат', function () {
    $this->order->update(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);
    $this->product->update(['stock' => 0]);
    apiSays('succeeded');

    sendWebhook('payment.succeeded', ['id' => 'pay-100'])->assertOk();

    expect($this->order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($this->payment->fresh()->status)->toBe(PaymentStatus::Succeeded);
    Notification::assertSentTo($this->admin, PaymentNeedsAttention::class, fn ($n) => str_contains($n->problem, 'возврат'));
    Notification::assertNotSentTo($this->order, OrderPaid::class);
});

it('вторая оплата уже оплаченного заказа — сигнал админу про возврат лишнего платежа', function () {
    apiSays('succeeded');
    sendWebhook('payment.succeeded', ['id' => 'pay-100'])->assertOk();

    $second = createdPayment($this->order, 'pay-200');
    Http::fake(['api.yookassa.ru/v3/payments/pay-200' => Http::response(yookassaPayment('pay-200', 'succeeded', $this->order->total))]);

    sendWebhook('payment.succeeded', ['id' => 'pay-200'])->assertOk();

    expect($second->fresh()->status)->toBe(PaymentStatus::Succeeded);
    Notification::assertSentTo($this->admin, PaymentNeedsAttention::class, fn ($n) => str_contains($n->problem, 'Повторная оплата'));
    Notification::assertSentToTimes($this->order, OrderPaid::class, 1);
});
