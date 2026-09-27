<?php

namespace App\Notifications;

class OrderPlaced extends OrderNotification
{
    protected function subject(): string
    {
        return "Заказ №{$this->order->number} оформлен";
    }

    protected function intro(): array
    {
        $due = $this->order->payment_due_at?->timezone(config('shop.timezone'))->format('H:i');

        return [
            "{$this->order->customer_name}, спасибо за заказ!",
            'Мы зарезервировали товары за вами'.($due ? " до {$due} (МСК)" : '').'. Если не успели оплатить на сайте — оплатите по кнопке ниже.',
        ];
    }

    protected function actionText(): string
    {
        return 'Перейти к оплате';
    }
}
