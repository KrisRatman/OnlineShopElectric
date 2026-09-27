<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;

/**
 * Письмо покупателю о заказе. Уходит через очередь и только после коммита
 * транзакции — иначе воркер может прочитать заказ раньше, чем он сохранится.
 */
#[DeleteWhenMissingModels]
abstract class OrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    abstract protected function subject(): string;

    /** @return list<string> */
    abstract protected function intro(): array;

    protected function actionText(): string
    {
        return 'Открыть заказ';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing('items');

        return (new MailMessage)
            ->subject($this->subject())
            ->markdown('mail.order', [
                'order' => $order,
                'intro' => $this->intro(),
                'actionText' => $this->actionText(),
                'actionUrl' => route('orders.show', $order->token),
            ]);
    }
}
