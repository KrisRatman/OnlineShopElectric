<?php

namespace App\Notifications\Admin;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;

/**
 * Администратору: пришла оплата — пора собирать заказ. Колокольчик в админке + письмо.
 */
#[DeleteWhenMissingModels]
class NewPaidOrder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Оплачен заказ №{$this->order->number} на ".money_rub($this->order->total))
            ->line("Покупатель: {$this->order->customer_name}, {$this->order->customer_phone}.")
            ->line("Доставка: {$this->order->delivery_method->getLabel()}.")
            ->action('Открыть в админке', OrderResource::getUrl('view', ['record' => $this->order], panel: 'admin'));
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title("Оплачен заказ №{$this->order->number}")
            ->body(money_rub($this->order->total).' · '.$this->order->customer_name)
            ->icon('heroicon-o-banknotes')
            ->iconColor('success')
            ->actions([
                Action::make('open')
                    ->label('Открыть')
                    ->url(OrderResource::getUrl('view', ['record' => $this->order], panel: 'admin'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
