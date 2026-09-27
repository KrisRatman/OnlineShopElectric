<?php

namespace App\Services\YooKassa;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Минимальный клиент API ЮKassa v3: создать платёж и прочитать платёж.
 *
 * Свой клиент на Http вместо официального SDK: две операции, и в тестах
 * его целиком подменяет Http::fake().
 *
 * Авторизация — HTTP Basic: shopId и секретный ключ. Повторный POST с тем же
 * заголовком Idempotence-Key ЮKassa не выполняет заново, а возвращает прежний
 * результат — так двойной клик или повтор после таймаута не создадут второй платёж.
 */
class YooKassaClient
{
    public function __construct(
        private readonly ?string $shopId,
        private readonly ?string $secretKey,
        private readonly string $baseUrl,
        private readonly int $timeout = 15,
    ) {}

    public function isConfigured(): bool
    {
        return filled($this->shopId) && filled($this->secretKey);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createPayment(array $payload, string $idempotenceKey): RemotePayment
    {
        $response = $this->send(fn (PendingRequest $http) => $http
            ->withHeaders(['Idempotence-Key' => $idempotenceKey])
            ->post('payments', $payload));

        return RemotePayment::fromArray($response->json());
    }

    public function getPayment(string $paymentId): RemotePayment
    {
        $response = $this->send(fn (PendingRequest $http) => $http->get('payments/'.rawurlencode($paymentId)));

        return RemotePayment::fromArray($response->json());
    }

    /**
     * @param  callable(PendingRequest): Response  $request
     */
    private function send(callable $request): Response
    {
        if (! $this->isConfigured()) {
            throw new YooKassaException('ЮKassa не настроена: заполните YOOKASSA_SHOP_ID и YOOKASSA_SECRET_KEY.');
        }

        $http = Http::baseUrl($this->baseUrl)
            ->withBasicAuth((string) $this->shopId, (string) $this->secretKey)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            // Сетевые сбои и 5xx повторяем: POST безопасен благодаря Idempotence-Key.
            ->retry(2, 500, fn ($e) => $e instanceof ConnectionException
                || ($e instanceof RequestException && $e->response->serverError()), throw: false);

        try {
            $response = $request($http);
        } catch (ConnectionException $e) {
            throw new YooKassaException('ЮKassa не ответила: '.$e->getMessage(), previous: $e);
        }

        if ($response->failed()) {
            $error = $response->json('description') ?? $response->body();

            throw new YooKassaException("ЮKassa вернула {$response->status()}: {$error}");
        }

        return $response;
    }
}
