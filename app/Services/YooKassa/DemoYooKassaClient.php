<?php

namespace App\Services\YooKassa;

use App\Enums\PaymentStatus;
use App\Support\Money;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Эмулятор API ЮKassa для демо-стенда, пока магазин не подключён к ЮKassa.
 *
 * Платёж живёт в кэше, «страница оплаты» — /demo-payment/{id} внутри магазина.
 * Остальной код (webhook-логика sync(), страница возврата, отмена по таймауту)
 * работает с ним точно так же, как с настоящей ЮKassa. Реальные деньги не списываются.
 */
class DemoYooKassaClient extends YooKassaClient
{
    private const TTL = 60 * 60 * 24;

    public function __construct()
    {
        parent::__construct(null, null, '');
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function createPayment(array $payload, string $idempotenceKey): RemotePayment
    {
        // Как в ЮKassa: повтор с тем же ключом возвращает тот же платёж.
        $id = Cache::get(self::key('idem', $idempotenceKey));

        if ($id === null) {
            $id = 'demo-'.Str::uuid();
            Cache::put(self::key('idem', $idempotenceKey), $id, self::TTL);
            Cache::put(self::key('payment', $id), [
                'id' => $id,
                'status' => PaymentStatus::Pending->value,
                'paid' => false,
                'amount' => $payload['amount'],
                'description' => $payload['description'] ?? '',
                'metadata' => $payload['metadata'] ?? [],
                'return_url' => $payload['confirmation']['return_url'],
                'confirmation' => ['type' => 'redirect', 'confirmation_url' => route('demo-payment.show', $id)],
                'test' => true,
            ], self::TTL);
        }

        return $this->getPayment($id);
    }

    public function getPayment(string $paymentId): RemotePayment
    {
        $data = self::find($paymentId);

        if ($data === null) {
            throw new YooKassaException("Демо-платёж {$paymentId} не найден.");
        }

        return RemotePayment::fromArray($data);
    }

    /** @return array<string, mixed>|null */
    public static function find(string $paymentId): ?array
    {
        return Cache::get(self::key('payment', $paymentId));
    }

    /** Покупатель нажал «Оплатить» или «Отклонить» на демо-странице. */
    public static function complete(string $paymentId, bool $success): void
    {
        $data = self::find($paymentId);

        if ($data === null || $data['status'] !== PaymentStatus::Pending->value) {
            return;
        }

        $data['status'] = $success ? PaymentStatus::Succeeded->value : PaymentStatus::Canceled->value;
        $data['paid'] = $success;

        if (! $success) {
            $data['cancellation_details'] = ['party' => 'yoo_money', 'reason' => 'canceled_by_merchant'];
        }

        Cache::put(self::key('payment', $paymentId), $data, self::TTL);
    }

    public static function amount(array $data): int
    {
        return Money::fromDecimal((string) $data['amount']['value']);
    }

    private static function key(string $type, string $id): string
    {
        return "demo-yookassa:{$type}:{$id}";
    }
}
