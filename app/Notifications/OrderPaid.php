<?php

namespace App\Notifications;

class OrderPaid extends OrderNotification
{
    protected function subject(): string
    {
        return "Оплата заказа №{$this->order->number} получена";
    }

    protected function intro(): array
    {
        return [
            "{$this->order->customer_name}, оплата прошла успешно.",
            'Мы уже собираем заказ и напишем, когда передадим его в доставку.',
        ];
    }
}
