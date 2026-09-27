<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property PaymentStatus $status
 * @property ?CarbonImmutable $paid_at
 */
#[Fillable([
    'order_id', 'provider', 'provider_payment_id', 'idempotence_key', 'status', 'amount',
    'confirmation_url', 'cancellation_reason', 'payload', 'paid_at',
])]
class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'payload' => 'array',
            'paid_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function cancellationReasonLabel(): ?string
    {
        return match ($this->cancellation_reason) {
            null => null,
            'expired_on_confirmation' => 'Истекло время на оплату',
            'insufficient_funds' => 'Недостаточно средств',
            'card_expired' => 'Истёк срок действия карты',
            'canceled_by_merchant' => 'Отменён магазином',
            'permission_revoked' => 'Покупатель отозвал разрешение',
            'fraud_suspected' => 'Подозрение на мошенничество',
            'call_issuer' => 'Банк отклонил платёж',
            '3d_secure_failed' => 'Не пройдена проверка 3-D Secure',
            'general_decline' => 'Платёж отклонён',
            default => $this->cancellation_reason,
        };
    }
}
