<?php

namespace App\Notifications;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Models\Order;

class OrderStatusChanged extends OrderNotification
{
    // Статус на момент события: к отправке письма из очереди он может уже смениться.
    public OrderStatus $status;

    public function __construct(Order $order)
    {
        parent::__construct($order);
        $this->status = $order->status;
    }

    protected function subject(): string
    {
        return "Заказ №{$this->order->number}: {$this->status->getLabel()}";
    }

    protected function intro(): array
    {
        return match ($this->status) {
            OrderStatus::Processing => ['Мы начали собирать ваш заказ.'],
            OrderStatus::Shipped => $this->order->delivery_method === DeliveryMethod::Pickup
                ? ['Заказ ждёт вас в пункте самовывоза: '.config('shop.delivery.pickup_address').'.']
                : ['Заказ передан курьеру. Он позвонит перед доставкой.'],
            OrderStatus::Completed => ['Заказ выполнен. Спасибо за покупку!'],
            OrderStatus::Cancelled => array_values(array_filter([
                'Заказ отменён.',
                $this->order->cancel_reason ? "Причина: {$this->order->cancel_reason}." : null,
                $this->order->paid_at ? 'Деньги вернутся на карту в течение нескольких дней.' : null,
            ])),
            default => ["Новый статус заказа: {$this->status->getLabel()}."],
        };
    }
}
