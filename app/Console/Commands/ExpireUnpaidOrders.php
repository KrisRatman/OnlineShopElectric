<?php

namespace App\Console\Commands;

use App\Actions\CancelOrder;
use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Services\Payments\PaymentService;
use App\Services\YooKassa\YooKassaException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Отмена неоплаченных заказов, у которых истёк срок резерва: товар возвращается на склад.
 *
 * Перед отменой сверяемся с ЮKassa — вдруг покупатель заплатил, а уведомление потерялось.
 */
#[Signature('shop:expire-orders')]
#[Description('Отменить неоплаченные заказы с истёкшим резервом и вернуть товар на склад')]
class ExpireUnpaidOrders extends Command
{
    public function handle(CancelOrder $cancelOrder, PaymentService $payments): int
    {
        $cancelled = 0;

        Order::query()
            ->where('status', OrderStatus::PendingPayment)
            ->where('payment_due_at', '<', now())
            ->with('payments')
            ->each(function (Order $order) use ($cancelOrder, $payments, &$cancelled): void {
                foreach ($order->payments as $payment) {
                    try {
                        $payments->refresh($payment);
                    } catch (YooKassaException $e) {
                        // ЮKassa недоступна — не рискуем отменить оплаченный заказ, попробуем в следующий раз.
                        $this->warn("Заказ №{$order->number}: не удалось проверить платёж ({$e->getMessage()})");

                        return;
                    }
                }

                try {
                    $cancelOrder->handle($order, 'Истёк срок оплаты', onlyFrom: OrderStatus::PendingPayment);
                    $cancelled++;
                } catch (InvalidOrderTransitionException) {
                    // Заказ оплатили в последний момент.
                }
            });

        $this->info("Отменено заказов: {$cancelled}");

        return self::SUCCESS;
    }
}
