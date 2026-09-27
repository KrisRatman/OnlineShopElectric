<?php

namespace App\Livewire;

use App\Models\Order;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentService;
use App\Services\YooKassa\YooKassaException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Страница заказа по секретной ссылке. Здесь же — оплата и ожидание подтверждения.
 */
#[Layout('layouts.app')]
class OrderPage extends Component
{
    /** Сколько раз подряд страница спросит ЮKassa о статусе, пока покупатель ждёт. */
    private const MAX_CHECKS = 20;

    #[Locked]
    public int $orderId;

    #[Url(except: false)]
    public bool $returned = false;

    public int $checks = 0;

    public function mount(Order $order): void
    {
        $this->orderId = $order->id;
    }

    public function order(): Order
    {
        return Order::query()->with(['items.product.mainImage', 'latestPayment'])->findOrFail($this->orderId);
    }

    /** Ждём ли подтверждения оплаты: покупатель вернулся из ЮKassa, а статус ещё не пришёл. */
    public function isWaitingForPayment(Order $order): bool
    {
        return $this->returned
            && $order->isAwaitingPayment()
            && $order->latestPayment?->status->isFinal() === false
            && $order->latestPayment->provider_payment_id !== null
            && $this->checks < self::MAX_CHECKS;
    }

    public function checkPayment(PaymentService $payments): void
    {
        $this->checks++;
        $payment = $this->order()->latestPayment;

        if ($payment === null) {
            return;
        }

        try {
            $payments->refresh($payment);
        } catch (YooKassaException) {
            // Следующая попытка — через несколько секунд.
        }
    }

    public function pay(PaymentService $payments): void
    {
        try {
            $payment = $payments->start($this->order());
            $this->redirect($payment->confirmation_url);
        } catch (PaymentException $e) {
            session()->flash('error', $e->getMessage());
            $this->redirectRoute('orders.show', $this->order()->token);
        }
    }

    public function render(): View
    {
        $order = $this->order();

        return view('livewire.order-page', [
            'order' => $order,
            'waiting' => $this->isWaitingForPayment($order),
        ])->title("Заказ №{$order->number}");
    }
}
