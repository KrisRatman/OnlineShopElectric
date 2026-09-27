<div class="mx-auto max-w-7xl px-4 py-6">
    <x-breadcrumbs :items="[['Корзина', null]]" />
    <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Корзина</h1>

    @if ($summary->isEmpty())
        <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <x-shop-icon name="cart" class="mx-auto size-12 text-slate-300" />
            <p class="mt-3 text-lg font-bold text-slate-800">В корзине пока пусто</p>
            <p class="mt-1 text-sm text-slate-500">Загляните в каталог — там много интересного.</p>
            <a href="{{ route('catalog') }}" class="mt-5 inline-block rounded-xl bg-brand-600 px-6 py-3 font-bold text-white hover:bg-brand-700">Перейти в каталог</a>
        </div>
    @else
        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_22rem]">
            <div class="rounded-2xl border border-slate-200 bg-white">
                <ul class="divide-y divide-slate-100" wire:loading.class="opacity-60">
                    @foreach ($summary->lines as $line)
                        @php($product = $line->product)
                        <li class="flex gap-4 p-4 sm:p-5" wire:key="line-{{ $product->id }}" data-cart-line="{{ $product->slug }}">
                            <a href="{{ route('products.show', $product) }}" class="size-20 shrink-0 rounded-xl bg-slate-50 p-2 sm:size-24">
                                <x-product-image :product="$product" :class="\Illuminate\Support\Arr::toCssClasses(['size-full object-contain', 'opacity-40 grayscale' => $line->unavailable])" />
                            </a>
                            <div class="flex min-w-0 flex-1 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <a href="{{ route('products.show', $product) }}" class="line-clamp-2 font-semibold text-slate-900 hover:text-brand-600">{{ $product->name }}</a>
                                    <div class="mt-1 text-sm text-slate-500">{{ money_rub($line->unitPrice()) }} за шт.</div>
                                    @if ($line->notice)
                                        <div @class(['mt-1.5 text-sm font-medium', 'text-rose-600' => $line->unavailable, 'text-amber-600' => ! $line->unavailable])>{{ $line->notice }}</div>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between gap-4 sm:flex-col sm:items-end">
                                    @unless ($line->unavailable)
                                        <div class="flex items-center rounded-xl border border-slate-200">
                                            <button type="button" wire:click="setQuantity({{ $product->id }}, {{ $line->quantity - 1 }})" class="grid size-9 place-items-center text-slate-500 hover:text-brand-600" aria-label="Меньше">
                                                <x-shop-icon name="minus" class="size-4" />
                                            </button>
                                            <span class="w-8 text-center font-bold" data-quantity>{{ $line->quantity }}</span>
                                            <button type="button" wire:click="setQuantity({{ $product->id }}, {{ $line->quantity + 1 }})" @disabled($line->quantity >= $line->maxQuantity())
                                                    class="grid size-9 place-items-center text-slate-500 hover:text-brand-600 disabled:opacity-30" aria-label="Больше">
                                                <x-shop-icon name="plus" class="size-4" />
                                            </button>
                                        </div>
                                        <div class="text-lg font-extrabold text-slate-900">{{ money_rub($line->total()) }}</div>
                                    @endunless
                                    <button type="button" wire:click="remove({{ $product->id }})" class="flex items-center gap-1 text-sm text-slate-400 hover:text-rose-600">
                                        <x-shop-icon name="trash" class="size-4" /> Удалить
                                    </button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="flex justify-end border-t border-slate-100 px-5 py-3">
                    <button type="button" wire:click="clear" wire:confirm="Очистить корзину?" class="text-sm font-semibold text-slate-400 hover:text-rose-600">Очистить корзину</button>
                </div>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-36">
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <dl class="space-y-2.5 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Товары, {{ $summary->itemsCount() }} шт.</dt>
                            <dd class="font-semibold">{{ money_rub($summary->subtotal()) }}</dd>
                        </div>
                        @if ($summary->savings() > 0)
                            <div class="flex justify-between text-rose-600">
                                <dt>Скидка</dt>
                                <dd class="font-semibold">−{{ money_rub($summary->savings()) }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Доставка курьером</dt>
                            <dd class="font-semibold">{{ $courierPrice ? money_rub($courierPrice) : 'бесплатно' }}</dd>
                        </div>
                    </dl>
                    <div class="mt-4 flex items-baseline justify-between border-t border-slate-100 pt-4">
                        <span class="font-bold text-slate-900">Итого</span>
                        <span class="text-2xl font-extrabold text-slate-900" data-cart-total>{{ money_rub($summary->subtotal()) }}</span>
                    </div>
                    <p class="mt-1 text-right text-xs text-slate-400">без учёта доставки</p>

                    @if ($summary->canCheckout())
                        <a href="{{ route('checkout') }}" class="mt-5 block rounded-xl bg-brand-600 px-6 py-3.5 text-center font-bold text-white shadow-sm shadow-brand-600/30 transition hover:bg-brand-700">
                            Перейти к оформлению
                        </a>
                    @else
                        <div class="mt-5 rounded-xl bg-slate-100 px-4 py-3 text-center text-sm font-semibold text-slate-500">Нет товаров в наличии для заказа</div>
                    @endif
                    @if ($summary->hasUnavailable() && $summary->canCheckout())
                        <p class="mt-3 text-xs text-slate-500">Товары, которых нет в наличии, в заказ не попадут.</p>
                    @endif
                </div>

                @if ($courierPrice > 0)
                    @php($left = $freeFrom - $summary->subtotal())
                    <div class="rounded-2xl border border-brand-100 bg-brand-50 p-4 text-sm text-brand-800">
                        <div class="flex items-center gap-2 font-semibold"><x-shop-icon name="truck" class="size-5" /> До бесплатной доставки {{ money_rub($left) }}</div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-brand-100">
                            <div class="h-full rounded-full bg-brand-500" style="width: {{ min(100, round($summary->subtotal() * 100 / $freeFrom)) }}%"></div>
                        </div>
                    </div>
                @endif
            </aside>
        </div>
    @endif
</div>
