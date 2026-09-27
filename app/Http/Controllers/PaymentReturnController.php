<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payments\PaymentService;
use App\Services\YooKassa\YooKassaException;
use Illuminate\Http\RedirectResponse;

/**
 * Сюда ЮKassa возвращает покупателя после оплаты.
 *
 * Сам факт возврата ничего не доказывает (адрес можно открыть руками), поэтому
 * спрашиваем статус у ЮKassa. Обычно уведомление уже пришло, а если ещё нет —
 * заказ станет оплаченным прямо здесь. Работает и локально, без публичного webhook.
 */
class PaymentReturnController extends Controller
{
    public function __invoke(Order $order, PaymentService $payments): RedirectResponse
    {
        $payment = $order->latestPayment;

        if ($payment !== null) {
            try {
                $payments->refresh($payment);
            } catch (YooKassaException) {
                // Не страшно: страница заказа сама перепроверит статус, придёт и webhook.
            }
        }

        return redirect()->route('orders.show', ['order' => $order->token, 'returned' => 1]);
    }
}
