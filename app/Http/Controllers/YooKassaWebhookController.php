<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payments\PaymentService;
use App\Services\YooKassa\YooKassaClient;
use App\Services\YooKassa\YooKassaException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Уведомление ЮKassa о смене статуса платежа.
 *
 * ЮKassa повторяет уведомление, пока не получит 200, поэтому:
 * - всё, что мы обработали или сознательно пропустили, отвечаем 200;
 * - 500 — только когда стоит попробовать позже (API ЮKassa недоступен).
 */
class YooKassaWebhookController extends Controller
{
    public function __invoke(Request $request, YooKassaClient $client, PaymentService $payments): Response
    {
        $paymentId = $request->input('object.id');

        if ($request->input('type') !== 'notification' || ! is_string($paymentId) || $paymentId === '') {
            return response('Bad request', 400);
        }

        $payment = Payment::query()->where('provider_payment_id', $paymentId)->first();

        if ($payment === null) {
            // Чужой или удалённый платёж: повторы нам не помогут.
            Log::warning('ЮKassa: уведомление о неизвестном платеже', ['id' => $paymentId, 'event' => $request->input('event')]);

            return response('OK');
        }

        try {
            // Телу уведомления не верим: статус и сумму берём из API.
            $remote = $client->getPayment($paymentId);
        } catch (YooKassaException $e) {
            Log::error('ЮKassa: не удалось проверить платёж из уведомления', ['id' => $paymentId, 'error' => $e->getMessage()]);

            return response('Try later', 500);
        }

        $payments->sync($payment, $remote);

        return response('OK');
    }
}
