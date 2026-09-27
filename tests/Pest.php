<?php

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature-тесты работают с приложением и базой, Unit — чистый PHP.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| ЮKassa
|--------------------------------------------------------------------------
*/

/** IP из списка адресов ЮKassa — с него приходят уведомления. */
const YOOKASSA_IP = '185.71.76.10';

/**
 * Ответ API ЮKassa с объектом платежа.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function yookassaPayment(string $id, string $status, int $kopecks, array $overrides = []): array
{
    return array_replace_recursive([
        'id' => $id,
        'status' => $status,
        'paid' => $status === 'succeeded',
        'amount' => ['value' => sprintf('%d.%02d', intdiv($kopecks, 100), $kopecks % 100), 'currency' => 'RUB'],
        'confirmation' => ['type' => 'redirect', 'confirmation_url' => "https://yoomoney.ru/checkout/payments/v2/contract?orderId={$id}"],
        'created_at' => now()->toIso8601String(),
        'description' => 'Заказ',
        'metadata' => [],
        'recipient' => ['account_id' => '123456', 'gateway_id' => '1'],
        'refundable' => $status === 'succeeded',
        'test' => true,
    ], $status === 'canceled' ? ['cancellation_details' => ['party' => 'yoo_money', 'reason' => 'expired_on_confirmation']] : [], $overrides);
}

/** Платёж, уже созданный в ЮKassa: у заказа есть ссылка на оплату. */
function createdPayment(Order $order, ?string $providerId = null): Payment
{
    return $order->payments()->create([
        'provider' => 'yookassa',
        'provider_payment_id' => $providerId ?? (string) Str::uuid(),
        'idempotence_key' => (string) Str::uuid(),
        'status' => 'pending',
        'amount' => $order->total,
        'confirmation_url' => 'https://yoomoney.ru/checkout/payments/v2/contract?orderId=x',
    ]);
}

/**
 * Уведомление ЮKassa, как его шлёт ЮKassa: JSON с IP из белого списка.
 *
 * @param  array<string, mixed>  $object
 */
function sendWebhook(string $event, array $object, string $ip = YOOKASSA_IP): TestResponse
{
    return test()
        ->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson('/payments/yookassa/webhook', ['type' => 'notification', 'event' => $event, 'object' => $object]);
}
