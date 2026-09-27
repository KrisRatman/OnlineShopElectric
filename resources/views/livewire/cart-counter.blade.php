<a href="{{ route('cart') }}" class="relative flex flex-col items-center rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 hover:text-brand-600" title="Корзина" data-cart-counter>
    <x-shop-icon name="cart" class="size-6" />
    <span class="hidden md:block">Корзина</span>
    @if ($count > 0)
        <span class="absolute -top-0.5 right-0.5 grid min-w-5 place-items-center rounded-full bg-volt-400 px-1 text-[11px] font-extrabold leading-5 text-slate-900">{{ $count > 99 ? '99+' : $count }}</span>
    @endif
</a>
