<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Статусы платежа ЮKassa один в один: значения приходят из API.
 */
enum PaymentStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case WaitingForCapture = 'waiting_for_capture';
    case Succeeded = 'succeeded';
    case Canceled = 'canceled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает оплаты',
            self::WaitingForCapture => 'Ждёт подтверждения',
            self::Succeeded => 'Оплачен',
            self::Canceled => 'Отменён',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending, self::WaitingForCapture => 'warning',
            self::Succeeded => 'success',
            self::Canceled => 'danger',
        };
    }

    /** Итоговый статус: дальше платёж не меняется. */
    public function isFinal(): bool
    {
        return $this === self::Succeeded || $this === self::Canceled;
    }
}
