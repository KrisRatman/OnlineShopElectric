<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PendingPayment => 'Ожидает оплаты',
            self::Paid => 'Оплачен',
            self::Processing => 'Собирается',
            self::Shipped => 'Передан в доставку',
            self::Completed => 'Выполнен',
            self::Cancelled => 'Отменён',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PendingPayment => 'warning',
            self::Paid => 'success',
            self::Processing, self::Shipped => 'info',
            self::Completed => 'gray',
            self::Cancelled => 'danger',
        };
    }

    /**
     * Куда заказ может перейти из текущего статуса вручную (из админки).
     * В «Оплачен» переводит только платёж, в «Ожидает оплаты» не возвращаются.
     *
     * @return list<self>
     */
    public function manualTransitions(): array
    {
        return match ($this) {
            self::PendingPayment => [self::Cancelled],
            self::Paid => [self::Processing, self::Shipped, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Completed],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->manualTransitions(), true);
    }

    /** Оплаченные заказы: считаются в выручке. */
    public function isPaid(): bool
    {
        return in_array($this, [self::Paid, self::Processing, self::Shipped, self::Completed], true);
    }

    /** Классы бейджа на витрине. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::PendingPayment => 'bg-amber-100 text-amber-800',
            self::Paid => 'bg-emerald-100 text-emerald-800',
            self::Processing, self::Shipped => 'bg-sky-100 text-sky-800',
            self::Completed => 'bg-slate-100 text-slate-700',
            self::Cancelled => 'bg-rose-100 text-rose-800',
        };
    }
}
