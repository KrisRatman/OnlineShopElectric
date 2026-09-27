@php
    $payment = $order->latestPayment;
    $tz = config('shop.timezone');
@endphp

<div class="mx-auto max-w-5xl px-4 py-6" @if ($waiting) wire:poll.3s="checkPayment" @endif>
    <x-breadcrumbs :items="auth()->check() ? [['Личный кабинет', route('account')], ['Заказ №'.$order->number, null]] : [['Заказ №'.$order->number, null]]" />

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Заказ №{{ $order->number }}</h1>
        <span class="rounded-full px-3 py-1 text-sm font-bold {{ $order->status->badgeClasses() }}" data-order-status="{{ $order->status->value }}">{{ $order->status->getLabel() }}</span>
    </div>
    <p class="mt-1 text-sm text-slate-500">от {{ $order->created_at->timezone($tz)->isoFormat('D MMMM YYYY, HH:mm') }}</p>

    {{-- Главный блок: что происходит с оплатой --}}
    <div class="mt-6">
        @if ($order->status->isPaid())
            <div class="flex items-start gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                <span class="grid size-12 shrink-0 place-items-center rounded-full bg-emerald-500 text-white"><x-shop-icon name="check" class="size-6" stroke-width="2.5" /></span>
                <div>
                    <div class="text-lg font-bold text-emerald-900">
                        {{ $order->status === \App\Enums\OrderStatus::Paid ? 'Оплата получена, спасибо!' : 'Заказ оплачен' }}
                    </div>
                    <p class="mt-1 text-sm text-emerald-800">
                        Оплачено {{ $order->paid_at?->timezone($tz)->isoFormat('D MMMM, HH:mm') }}. Письмо с подтверждением отправлено на {{ $order->customer_email }}.
                    </p>
                </div>
            </div>
        @elseif ($order->status === \App\Enums\OrderStatus::Cancelled)
            <div class="flex items-start gap-4 rounded-2xl border border-rose-200 bg-rose-50 p-5">
                <span class="grid size-12 shrink-0 place-items-center rounded-full bg-rose-500 text-white"><x-shop-icon name="x" class="size-6" stroke-width="2.5" /></span>
                <div>
                    <div class="text-lg font-bold text-rose-900">Заказ отменён</div>
                    @if ($order->cancel_reason)
                        <p class="mt-1 text-sm text-rose-800">Причина: {{ $order->cancel_reason }}.</p>
                    @endif
                    <a href="{{ route('catalog') }}" class="mt-3 inline-block text-sm font-bold text-rose-700 underline">Вернуться в каталог</a>
                </div>
            </div>
        @elseif ($waiting)
            <div class="flex items-start gap-4 rounded-2xl border border-brand-200 bg-brand-50 p-5" data-waiting-payment>
                <span class="grid size-12 shrink-0 place-items-center rounded-full bg-brand-600 text-white">
                    <svg class="size-6 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                </span>
                <div>
                    <div class="text-lg font-bold text-brand-900">Проверяем оплату…</div>
                    <p class="mt-1 text-sm text-brand-800">Обычно это занимает несколько секунд. Страница обновится сама.</p>
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <span class="grid size-12 shrink-0 place-items-center rounded-full bg-amber-400 text-slate-900"><x-shop-icon name="clock" class="size-6" /></span>
                        <div>
                            <div class="text-lg font-bold text-amber-900">Заказ ждёт оплаты</div>
                            <p class="mt-1 text-sm text-amber-800">
                                Товар зарезервирован до {{ $order->payment_due_at?->timezone($tz)->format('H:i') }} (МСК). Потом резерв снимется, а заказ отменится.
                            </p>
                            @if ($payment?->status === \App\Enums\PaymentStatus::Canceled)
                                <p class="mt-2 text-sm font-semibold text-rose-700">Прошлая попытка не удалась: {{ mb_strtolower($payment->cancellationReasonLabel() ?? 'платёж отменён') }}. Попробуйте ещё раз.</p>
                            @endif
                        </div>
                    </div>
                    <button type="button" wire:click="pay" wire:loading.attr="disabled"
                            class="flex items-center gap-2 rounded-xl bg-brand-600 px-6 py-3 font-bold text-white shadow-sm shadow-brand-600/30 hover:bg-brand-700 disabled:opacity-70">
                        <x-shop-icon name="card" class="size-5" />
                        <span wire:loading.remove wire:target="pay">Оплатить {{ money_rub($order->total) }}</span>
                        <span wire:loading wire:target="pay">Переходим к оплате…</span>
                    </button>
                </div>
            </div>
        @endif
    </div>

    <div class="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_20rem]">
        <section class="rounded-2xl border border-slate-200 bg-white">
            <h2 class="border-b border-slate-100 px-5 py-4 font-bold text-slate-900">Состав заказа</h2>
            <ul class="divide-y divide-slate-100">
                @foreach ($order->items as $item)
                    <li class="flex items-center gap-4 px-5 py-4">
                        <div class="size-14 shrink-0 rounded-lg bg-slate-50 p-1">
                            @if ($item->product)
                                <x-product-image :product="$item->product" class="size-full object-contain" />
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            @if ($item->product?->is_active)
                                <a href="{{ route('products.show', $item->product) }}" class="line-clamp-2 font-medium text-slate-900 hover:text-brand-600">{{ $item->product_name }}</a>
                            @else
                                <div class="line-clamp-2 font-medium text-slate-900">{{ $item->product_name }}</div>
                            @endif
                            <div class="text-sm text-slate-400">{{ $item->quantity }} × {{ money_rub($item->price) }}</div>
                        </div>
                        <div class="font-semibold">{{ money_rub($item->total) }}</div>
                    </li>
                @endforeach
            </ul>
            <dl class="space-y-2 border-t border-slate-100 px-5 py-4 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Товары</dt><dd class="font-semibold">{{ money_rub($order->subtotal) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Доставка</dt><dd class="font-semibold">{{ $order->delivery_price ? money_rub($order->delivery_price) : 'бесплатно' }}</dd></div>
                <div class="flex justify-between border-t border-slate-100 pt-3 text-base"><dt class="font-bold text-slate-900">Итого</dt><dd class="font-extrabold">{{ money_rub($order->total) }}</dd></div>
            </dl>
        </section>

        <aside class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm">
                <h2 class="font-bold text-slate-900">Получение</h2>
                <div class="mt-3 flex items-start gap-2 text-slate-600">
                    <x-shop-icon :name="$order->delivery_method === \App\Enums\DeliveryMethod::Courier ? 'truck' : 'map-pin'" class="size-5 shrink-0 text-brand-600" />
                    <div>
                        <div class="font-semibold text-slate-800">{{ $order->delivery_method->getLabel() }}</div>
                        {{ $order->delivery_address ?? config('shop.delivery.pickup_address') }}
                    </div>
                </div>
                @if ($order->comment)
                    <p class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-slate-600">{{ $order->comment }}</p>
                @endif
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm">
                <h2 class="font-bold text-slate-900">Покупатель</h2>
                <div class="mt-3 space-y-1 text-slate-600">
                    <div class="font-semibold text-slate-800">{{ $order->customer_name }}</div>
                    <div>{{ \App\Support\Phone::format($order->customer_phone) }}</div>
                    <div>{{ $order->customer_email }}</div>
                </div>
            </div>
            @guest
                <p class="px-1 text-xs text-slate-400">Сохраните ссылку на эту страницу — по ней можно вернуться к заказу. Ссылка есть и в письме.</p>
            @endguest
        </aside>
    </div>
</div>
