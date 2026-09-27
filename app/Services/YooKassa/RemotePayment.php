<?php

namespace App\Services\YooKassa;

use App\Enums\PaymentStatus;
use App\Support\Money;

/**
 * Платёж в том виде, в каком его вернул API ЮKassa.
 */
final readonly class RemotePayment
{
    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public PaymentStatus $status,
        public int $amount,
        public string $currency,
        public bool $paid,
        public ?string $confirmationUrl,
        public ?string $cancellationReason,
        public array $metadata,
        public array $raw,
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            status: PaymentStatus::from($data['status']),
            amount: Money::fromDecimal((string) $data['amount']['value']),
            currency: (string) $data['amount']['currency'],
            paid: (bool) ($data['paid'] ?? false),
            confirmationUrl: $data['confirmation']['confirmation_url'] ?? null,
            cancellationReason: $data['cancellation_details']['reason'] ?? null,
            metadata: (array) ($data['metadata'] ?? []),
            raw: $data,
        );
    }
}
