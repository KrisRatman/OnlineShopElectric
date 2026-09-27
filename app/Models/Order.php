<?php

namespace App\Models;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * @property OrderStatus $status
 * @property DeliveryMethod $delivery_method
 * @property ?CarbonImmutable $payment_due_at
 * @property ?CarbonImmutable $paid_at
 */
#[Fillable([
    'number', 'user_id', 'token', 'status',
    'customer_name', 'customer_email', 'customer_phone',
    'delivery_method', 'delivery_address', 'comment',
    'subtotal', 'delivery_price', 'total',
    'payment_due_at', 'paid_at', 'cancelled_at', 'cancel_reason',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            $order->token ??= Str::random(40);
        });

        // Номер — из id: короткий, по порядку и без гонок при одновременных заказах.
        static::created(function (Order $order): void {
            if ($order->number === null) {
                $order->forceFill(['number' => sprintf('%06d', $order->id)])->saveQuietly();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'delivery_method' => DeliveryMethod::class,
            'subtotal' => 'integer',
            'delivery_price' => 'integer',
            'total' => 'integer',
            'payment_due_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('id');
    }

    /** @return HasOne<Payment, $this> */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /** Письма о заказе уходят на email из заказа — и гостю, и пользователю. */
    public function routeNotificationForMail(): string
    {
        return $this->customer_email;
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === OrderStatus::PendingPayment;
    }

    /** Есть ли платёж, по которому покупатель, возможно, прямо сейчас платит. */
    public function hasPendingPayment(): bool
    {
        return $this->payments()
            ->whereIn('status', [PaymentStatus::Pending, PaymentStatus::WaitingForCapture])
            ->exists();
    }

    public function itemsCount(): int
    {
        return (int) $this->items->sum('quantity');
    }
}
