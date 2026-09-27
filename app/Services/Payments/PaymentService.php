<?php

namespace App\Services\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Admin\NewPaidOrder;
use App\Notifications\Admin\PaymentNeedsAttention;
use App\Notifications\OrderPaid;
use App\Services\YooKassa\RemotePayment;
use App\Services\YooKassa\YooKassaClient;
use App\Services\YooKassa\YooKassaException;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Платёжный поток ЮKassa.
 *
 * 1. start(): создаём платёж в ЮKassa и отдаём ссылку на страницу оплаты.
 * 2. Покупатель платит на стороне ЮKassa.
 * 3. ЮKassa присылает уведомление (webhook). Мы ему не верим, а перечитываем
 *    платёж через API и вызываем sync() — единственное место, где заказ становится
 *    оплаченным. sync() идемпотентен: повторное уведомление ничего не меняет.
 */
class PaymentService
{
    public function __construct(private readonly YooKassaClient $client) {}

    /**
     * Получить платёж для заказа: существующий, если по нему ещё можно заплатить, или новый.
     *
     * @throws PaymentException
     */
    public function start(Order $order): Payment
    {
        if (! $order->isAwaitingPayment()) {
            throw new PaymentException('Заказ уже не ждёт оплаты.');
        }

        $payment = DB::transaction(function () use ($order): Payment {
            // Блокировка заказа: двойной клик «Оплатить» не создаст два платежа.
            Order::query()->whereKey($order->id)->lockForUpdate()->first();

            $pending = $order->payments()
                ->where('status', PaymentStatus::Pending)
                ->where('amount', $order->total)
                ->latest('id')
                ->first();

            return $pending ?? $order->payments()->create([
                'provider' => 'yookassa',
                'idempotence_key' => (string) Str::uuid(),
                'status' => PaymentStatus::Pending,
                'amount' => $order->total,
            ]);
        });

        // Платёж уже создан: сверяемся с ЮKassa — ссылка на оплату могла истечь.
        if ($payment->provider_payment_id !== null) {
            return $this->reuse($order, $payment);
        }

        // Если прошлый запрос к ЮKassa не удался, повторяем с тем же ключом
        // идемпотентности: второй платёж не появится.

        try {
            $remote = $this->client->createPayment($this->payload($order, $payment), $payment->idempotence_key);
        } catch (YooKassaException $e) {
            Log::error('ЮKassa: не удалось создать платёж', ['order' => $order->id, 'error' => $e->getMessage()]);

            throw new PaymentException('Платёжный сервис временно недоступен. Попробуйте ещё раз через минуту.', previous: $e);
        }

        $payment->update([
            'provider_payment_id' => $remote->id,
            'status' => $remote->status,
            'confirmation_url' => $remote->confirmationUrl,
            'payload' => $remote->raw,
        ]);

        return $payment;
    }

    private function reuse(Order $order, Payment $payment): Payment
    {
        try {
            $payment = $this->refresh($payment);
        } catch (YooKassaException $e) {
            Log::warning('ЮKassa: не удалось проверить платёж', ['payment' => $payment->id, 'error' => $e->getMessage()]);
        }

        return match ($payment->status) {
            PaymentStatus::Pending => $payment,
            PaymentStatus::Succeeded => throw new PaymentException('Заказ уже оплачен.'),
            PaymentStatus::WaitingForCapture => throw new PaymentException('Платёж обрабатывается. Обновите страницу через минуту.'),
            // Ссылка истекла или платёж отклонён — создаём новый.
            PaymentStatus::Canceled => $this->start($order->refresh()),
        };
    }

    /**
     * Перечитать платёж в ЮKassa и применить статус. Для страницы возврата и планировщика.
     */
    public function refresh(Payment $payment): Payment
    {
        if ($payment->provider_payment_id === null || $payment->status->isFinal()) {
            return $payment;
        }

        return $this->sync($payment, $this->client->getPayment($payment->provider_payment_id));
    }

    /**
     * Применить к платежу и заказу статус из ЮKassa. Идемпотентно.
     */
    public function sync(Payment $payment, RemotePayment $remote): Payment
    {
        if ($remote->id !== $payment->provider_payment_id) {
            throw new PaymentException("Платёж {$remote->id} не относится к записи #{$payment->id}.");
        }

        $events = [];

        $payment = DB::transaction(function () use ($payment, $remote, &$events): Payment {
            // Под блокировкой: два одновременных уведомления обработаются по очереди,
            // и второе увидит, что платёж уже в итоговом статусе.
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status->isFinal() || $locked->status === $remote->status) {
                return $locked;
            }

            if ($remote->status === PaymentStatus::Succeeded && ! $this->amountMatches($locked, $remote)) {
                Log::critical('ЮKassa: сумма платежа не совпадает с заказом', [
                    'payment' => $locked->id,
                    'expected' => $locked->amount,
                    'actual' => $remote->amount,
                    'currency' => $remote->currency,
                ]);
                $events[] = ['attention', $locked, 'Сумма оплаты не совпадает с суммой заказа. Проверьте платёж в кабинете ЮKassa.'];

                return $locked;
            }

            $locked->update([
                'status' => $remote->status,
                'cancellation_reason' => $remote->cancellationReason,
                'payload' => $remote->raw,
                'paid_at' => $remote->status === PaymentStatus::Succeeded ? now() : null,
            ]);

            if ($remote->status === PaymentStatus::Succeeded) {
                $events = [...$events, ...$this->markOrderPaid($locked)];
            }

            return $locked;
        });

        $this->dispatch($events);

        return $payment;
    }

    /**
     * @return list<array{0: string, 1: Payment|Order, 2?: string}>
     */
    private function markOrderPaid(Payment $payment): array
    {
        /** @var Order $order */
        $order = Order::query()->whereKey($payment->order_id)->lockForUpdate()->firstOrFail();

        if ($order->status === OrderStatus::PendingPayment) {
            $order->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);

            return [['paid', $order]];
        }

        // Заказ успели отменить по таймауту, а покупатель всё-таки заплатил.
        // Пробуем снова зарезервировать товар; не вышло — нужен возврат денег.
        if ($order->status === OrderStatus::Cancelled && $order->paid_at === null) {
            if ($this->reserveAgain($order)) {
                $order->update([
                    'status' => OrderStatus::Paid,
                    'paid_at' => now(),
                    'cancelled_at' => null,
                    'cancel_reason' => null,
                ]);

                return [['paid', $order]];
            }

            return [['attention', $payment, "Оплачен заказ №{$order->number}, который уже отменён, а товара нет в наличии. Оформите возврат в кабинете ЮKassa."]];
        }

        // Заказ уже оплачен другим платежом — это вторая оплата.
        return [['attention', $payment, "Повторная оплата заказа №{$order->number}. Оформите возврат лишнего платежа в кабинете ЮKassa."]];
    }

    private function reserveAgain(Order $order): bool
    {
        $items = $order->items()->get();

        if ($items->contains(fn ($item) => $item->product_id === null)) {
            return false;
        }

        $products = Product::query()->whereKey($items->pluck('product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        foreach ($items as $item) {
            if (($products[$item->product_id]->stock ?? 0) < $item->quantity) {
                return false;
            }
        }

        foreach ($items as $item) {
            $products[$item->product_id]->decrement('stock', $item->quantity);
        }

        return true;
    }

    private function amountMatches(Payment $payment, RemotePayment $remote): bool
    {
        return $remote->currency === 'RUB' && $remote->amount === $payment->amount;
    }

    /**
     * Письма — после коммита транзакции, когда статус в базе уже точно сохранён.
     *
     * @param  list<array{0: string, 1: Payment|Order, 2?: string}>  $events
     */
    private function dispatch(array $events): void
    {
        foreach ($events as $event) {
            match ($event[0]) {
                'paid' => $this->notifyPaid($event[1]),
                'attention' => Notification::send(User::query()->where('is_admin', true)->get(), new PaymentNeedsAttention($event[1], $event[2])),
            };
        }
    }

    private function notifyPaid(Order $order): void
    {
        $order->notify(new OrderPaid($order));
        Notification::send(User::query()->where('is_admin', true)->get(), new NewPaidOrder($order));
    }

    /**
     * Тело запроса на создание платежа.
     *
     * @return array<string, mixed>
     */
    private function payload(Order $order, Payment $payment): array
    {
        $payload = [
            'amount' => ['value' => Money::toDecimal($payment->amount), 'currency' => 'RUB'],
            // Одностадийный платёж: деньги списываются сразу, без ручного подтверждения.
            'capture' => true,
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => route('orders.payment-return', $order->token),
            ],
            'description' => Str::limit("Заказ №{$order->number} в магазине ".config('shop.name'), 128, ''),
            'metadata' => ['order_id' => $order->id, 'payment_id' => $payment->id],
        ];

        if (config('yookassa.receipt.enabled')) {
            $payload['receipt'] = $this->receipt($order);
        }

        return $payload;
    }

    /**
     * Чек по 54-ФЗ: позиции в сумме обязаны дать сумму платежа.
     *
     * @return array<string, mixed>
     */
    private function receipt(Order $order): array
    {
        $vat = (int) config('yookassa.receipt.vat_code');

        $items = $order->items->map(fn ($item) => [
            'description' => Str::limit($item->product_name, 128, ''),
            'quantity' => $item->quantity.'.00',
            'amount' => ['value' => Money::toDecimal($item->price), 'currency' => 'RUB'],
            'vat_code' => $vat,
            'payment_mode' => 'full_payment',
            'payment_subject' => 'commodity',
        ])->all();

        if ($order->delivery_price > 0) {
            $items[] = [
                'description' => 'Доставка',
                'quantity' => '1.00',
                'amount' => ['value' => Money::toDecimal($order->delivery_price), 'currency' => 'RUB'],
                'vat_code' => $vat,
                'payment_mode' => 'full_payment',
                'payment_subject' => 'service',
            ];
        }

        return [
            'customer' => ['email' => $order->customer_email],
            'items' => $items,
        ];
    }
}
