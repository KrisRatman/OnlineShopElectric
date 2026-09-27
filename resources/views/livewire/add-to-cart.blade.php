<div>
    @if ($max < 1)
        <button type="button" disabled class="w-full cursor-not-allowed rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-bold text-slate-400">
            Нет в наличии
        </button>
    @elseif ($withQuantity)
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center rounded-xl border border-slate-200 bg-white">
                <button type="button" wire:click="decrement" class="grid size-12 place-items-center text-slate-500 hover:text-brand-600 disabled:opacity-40" @disabled($quantity <= 1) aria-label="Меньше">
                    <x-shop-icon name="minus" class="size-4" />
                </button>
                <span class="w-8 text-center text-lg font-bold" data-quantity>{{ $quantity }}</span>
                <button type="button" wire:click="increment" class="grid size-12 place-items-center text-slate-500 hover:text-brand-600 disabled:opacity-40" @disabled($quantity >= $max - $inCart) aria-label="Больше">
                    <x-shop-icon name="plus" class="size-4" />
                </button>
            </div>
            <button type="button" wire:click="add" wire:loading.attr="disabled" @disabled($inCart >= $max)
                    class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-brand-600 px-6 py-3.5 text-base font-bold text-white shadow-sm shadow-brand-600/30 transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none">
                <x-shop-icon name="cart" class="size-5" />
                <span wire:loading.remove wire:target="add">{{ $inCart >= $max ? 'Больше нет на складе' : 'Добавить в корзину' }}</span>
                <span wire:loading wire:target="add">Добавляем…</span>
            </button>
        </div>
        @if ($inCart > 0)
            <p class="mt-3 text-sm text-slate-500">
                В корзине: <span class="font-semibold text-slate-800">{{ $inCart }} шт.</span>
                <a href="{{ route('cart') }}" class="ml-1 font-semibold text-brand-600 hover:text-brand-700">Перейти в корзину →</a>
            </p>
        @endif
    @elseif ($inCart > 0)
        <a href="{{ route('cart') }}" class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-brand-600 px-4 py-2 text-sm font-bold text-brand-700 transition hover:bg-brand-50">
            <x-shop-icon name="check" class="size-4" /> В корзине · {{ $inCart }} шт.
        </a>
    @else
        <button type="button" wire:click="add" wire:loading.attr="disabled"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-700 disabled:opacity-70">
            <x-shop-icon name="cart" class="size-4" />
            <span wire:loading.remove wire:target="add">В корзину</span>
            <span wire:loading wire:target="add">Добавляем…</span>
        </button>
    @endif
</div>
