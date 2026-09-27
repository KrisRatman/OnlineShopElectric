@php($input = 'w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-100')

<div class="mx-auto max-w-7xl px-4 py-6">
    <x-breadcrumbs :items="[['Корзина', route('cart')], ['Оформление заказа', null]]" />
    <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Оформление заказа</h1>

    <form wire:submit="placeOrder" class="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_24rem]">
        <div class="space-y-6">
            @error('form')
                <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <x-shop-icon name="warning" class="mt-0.5 size-5 shrink-0" /> <span>{{ $message }} <a href="{{ route('cart') }}" class="font-semibold underline">Вернуться в корзину</a></span>
                </div>
            @enderror

            <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
                <h2 class="flex items-center gap-2.5 text-lg font-bold text-slate-900">
                    <span class="grid size-7 place-items-center rounded-full bg-brand-600 text-sm text-white">1</span> Покупатель
                </h2>
                @guest
                    <p class="mt-2 text-sm text-slate-500">Есть аккаунт? <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">Войдите</a> — заказ появится в личном кабинете.</p>
                @endguest
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <x-field label="Имя и фамилия" for="name" class="sm:col-span-2">
                        <input id="name" type="text" wire:model.blur="name" autocomplete="name" class="{{ $input }}" placeholder="Иван Петров">
                    </x-field>
                    <x-field label="Телефон" for="phone">
                        <input id="phone" type="tel" wire:model.blur="phone" autocomplete="tel" class="{{ $input }}" placeholder="+7 900 000-00-00">
                    </x-field>
                    <x-field label="Email" for="email" hint="Пришлём сюда чек и статус заказа">
                        <input id="email" type="email" wire:model.blur="email" autocomplete="email" class="{{ $input }}" placeholder="ivan@example.com">
                    </x-field>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
                <h2 class="flex items-center gap-2.5 text-lg font-bold text-slate-900">
                    <span class="grid size-7 place-items-center rounded-full bg-brand-600 text-sm text-white">2</span> Получение
                </h2>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <label @class([
                        'flex cursor-pointer items-start gap-3 rounded-xl border-2 p-4 transition',
                        'border-brand-500 bg-brand-50/50' => $delivery === 'courier',
                        'border-slate-200 hover:border-slate-300' => $delivery !== 'courier',
                    ])>
                        <input type="radio" wire:model.live="delivery" value="courier" class="mt-0.5 size-4 border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span>
                            <span class="flex items-center gap-2 font-bold text-slate-900"><x-shop-icon name="truck" class="size-5 text-brand-600" /> Курьером</span>
                            <span class="mt-1 block text-sm text-slate-500">1–2 дня · {{ $courierPrice ? money_rub($courierPrice) : 'бесплатно' }}</span>
                        </span>
                    </label>
                    <label @class([
                        'flex cursor-pointer items-start gap-3 rounded-xl border-2 p-4 transition',
                        'border-brand-500 bg-brand-50/50' => $delivery === 'pickup',
                        'border-slate-200 hover:border-slate-300' => $delivery !== 'pickup',
                    ])>
                        <input type="radio" wire:model.live="delivery" value="pickup" class="mt-0.5 size-4 border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span>
                            <span class="flex items-center gap-2 font-bold text-slate-900"><x-shop-icon name="map-pin" class="size-5 text-brand-600" /> Самовывоз</span>
                            <span class="mt-1 block text-sm text-slate-500">Сегодня · бесплатно</span>
                        </span>
                    </label>
                </div>
                @error('delivery') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror

                <div class="mt-5 space-y-4">
                    @if ($delivery === 'courier')
                        <x-field label="Адрес доставки" for="address">
                            <input id="address" type="text" wire:model.blur="address" autocomplete="street-address" class="{{ $input }}" placeholder="Москва, ул. Тверская, д. 1, кв. 10">
                        </x-field>
                    @else
                        <div class="flex items-start gap-3 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            <x-shop-icon name="map-pin" class="mt-0.5 size-5 shrink-0 text-brand-600" />
                            <span>Пункт выдачи: <b class="text-slate-800">{{ config('shop.delivery.pickup_address') }}</b>, ежедневно 10:00–21:00. Сообщим, когда заказ будет готов.</span>
                        </div>
                    @endif
                    <x-field label="Комментарий к заказу" for="comment">
                        <textarea id="comment" wire:model.blur="comment" rows="2" class="{{ $input }}" placeholder="Например: позвонить за час"></textarea>
                    </x-field>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
                <h2 class="flex items-center gap-2.5 text-lg font-bold text-slate-900">
                    <span class="grid size-7 place-items-center rounded-full bg-brand-600 text-sm text-white">3</span> Оплата
                </h2>
                <div class="mt-4 flex items-start gap-3 rounded-xl border-2 border-brand-500 bg-brand-50/50 p-4">
                    <x-shop-icon name="card" class="size-6 shrink-0 text-brand-600" />
                    <div class="text-sm">
                        <div class="font-bold text-slate-900">Банковской картой онлайн</div>
                        <div class="mt-1 text-slate-500">
                            @if (config('yookassa.demo'))
                                Демо-режим: откроется тестовая страница оплаты, деньги не списываются.
                            @else
                                После подтверждения вы перейдёте на защищённую страницу ЮKassa.
                            @endif
                            Товар резервируется за вами на {{ config('shop.payment_ttl') }} минут.
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <aside class="space-y-4 lg:sticky lg:top-36">
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="font-bold text-slate-900">Ваш заказ</h2>
                <ul class="mt-4 space-y-3">
                    @foreach ($summary->availableLines() as $line)
                        <li class="flex items-center gap-3 text-sm" wire:key="summary-{{ $line->product->id }}">
                            <div class="size-12 shrink-0 rounded-lg bg-slate-50 p-1">
                                <x-product-image :product="$line->product" class="size-full object-contain" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="line-clamp-1 font-medium text-slate-800">{{ $line->product->name }}</div>
                                <div class="text-slate-400">{{ $line->quantity }} × {{ money_rub($line->unitPrice()) }}</div>
                            </div>
                            <div class="font-semibold">{{ money_rub($line->total()) }}</div>
                        </li>
                    @endforeach
                </ul>

                <dl class="mt-5 space-y-2 border-t border-slate-100 pt-4 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Товары</dt>
                        <dd class="font-semibold">{{ money_rub($summary->subtotal()) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Доставка</dt>
                        <dd class="font-semibold" data-delivery-price>{{ $deliveryPrice ? money_rub($deliveryPrice) : 'бесплатно' }}</dd>
                    </div>
                </dl>
                <div class="mt-4 flex items-baseline justify-between border-t border-slate-100 pt-4">
                    <span class="font-bold text-slate-900">К оплате</span>
                    <span class="text-2xl font-extrabold text-slate-900" data-checkout-total>{{ money_rub($total) }}</span>
                </div>

                <label class="mt-5 flex cursor-pointer items-start gap-2.5 text-xs text-slate-500">
                    <input type="checkbox" wire:model="agree" class="mt-0.5 size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span>Согласен на обработку персональных данных и с условиями продажи</span>
                </label>
                @error('agree') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror

                <button type="submit" wire:loading.attr="disabled"
                        class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-6 py-3.5 font-bold text-white shadow-sm shadow-brand-600/30 transition hover:bg-brand-700 disabled:opacity-70">
                    <span wire:loading.remove wire:target="placeOrder">Оплатить {{ money_rub($total) }}</span>
                    <span wire:loading wire:target="placeOrder">Оформляем заказ…</span>
                </button>
                <p class="mt-3 flex items-center justify-center gap-1.5 text-xs text-slate-400"><x-shop-icon name="shield" class="size-4" /> Платёж защищён ЮKassa</p>
            </div>
        </aside>
    </form>
</div>
