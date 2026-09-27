<?php

namespace App\Notifications\Admin;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Администратору: с платежом что-то не так (повторная оплата, оплата отменённого
 * заказа, не та сумма) — нужен ручной разбор и, скорее всего, возврат.
 */
class PaymentNeedsAttention extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment, public string $problem)
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
            ->error()
            ->subject('Платёж требует внимания')
            ->line($this->problem)
            ->line("Платёж ЮKassa: {$this->payment->provider_payment_id}, сумма ".money_rub($this->payment->amount).'.')
            ->action('Открыть заказ', $this->url());
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Платёж требует внимания')
            ->body($this->problem)
            ->icon('heroicon-o-exclamation-triangle')
            ->iconColor('danger')
            ->actions([
                Action::make('open')->label('Открыть заказ')->url($this->url())->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    private function url(): string
    {
        return OrderResource::getUrl('view', ['record' => $this->payment->order_id], panel: 'admin');
    }
}
