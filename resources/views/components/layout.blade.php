@props(['title' => null])

<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — ' : '' }}{{ config('shop.name') }} — магазин электроники</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-full flex-col bg-slate-50 font-sans text-slate-800 antialiased">
    {{-- Верхняя полоса --}}
    <div class="bg-brand-950 text-xs text-brand-100">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-2">
            <div class="flex items-center gap-5">
                <span class="flex items-center gap-1.5"><x-shop-icon name="truck" class="size-4 text-volt-400" /> Доставка по Москве — бесплатно от {{ money_rub(config('shop.delivery.free_from')) }}</span>
                <span class="hidden items-center gap-1.5 md:flex"><x-shop-icon name="map-pin" class="size-4 text-volt-400" /> Самовывоз: {{ config('shop.delivery.pickup_address') }}</span>
            </div>
            <a href="tel:{{ preg_replace('/[^\d+]/', '', config('shop.phone')) }}" class="hidden font-semibold text-white hover:text-volt-300 sm:block">{{ config('shop.phone') }}</a>
        </div>
    </div>

    {{-- Шапка --}}
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur" x-data="{ menu: false }" @keydown.escape.window="menu = false">
        <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3 sm:gap-5">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5" aria-label="{{ config('shop.name') }} — на главную">
                <span class="grid size-10 place-items-center rounded-xl bg-brand-600 text-volt-400 shadow-sm shadow-brand-600/30">
                    <x-shop-icon name="bolt" class="size-6" stroke-width="2" />
                </span>
                <span class="hidden text-xl font-extrabold tracking-tight text-slate-900 sm:block">{{ config('shop.name') }}</span>
            </a>

            <div class="relative" @click.outside="menu = false">
                <button type="button" @click="menu = !menu" :aria-expanded="menu"
                        class="flex items-center gap-2 rounded-xl bg-brand-600 px-3.5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-700 sm:px-4">
                    <x-shop-icon name="menu" class="size-5" x-show="!menu" />
                    <x-shop-icon name="x" class="size-5" x-show="menu" x-cloak />
                    <span class="hidden sm:inline">Каталог</span>
                </button>

                <div x-show="menu" x-cloak x-transition.origin.top.left
                     class="absolute left-0 top-full mt-2 w-[min(92vw,40rem)] rounded-2xl border border-slate-200 bg-white p-4 shadow-xl">
                    <div class="grid gap-4 sm:grid-cols-3">
                        @foreach ($navCategories as $root)
                            <div>
                                <a href="{{ route('catalog.category', $root) }}" class="flex items-center gap-2 font-bold text-slate-900 hover:text-brand-600">
                                    <x-shop-icon :name="$root->icon()" class="size-5 text-brand-600" />
                                    {{ $root->name }}
                                </a>
                                <ul class="mt-2 space-y-1 border-l border-slate-100 pl-7 text-sm">
                                    @foreach ($root->children as $child)
                                        <li><a href="{{ route('catalog.category', $child) }}" class="text-slate-600 hover:text-brand-600">{{ $child->name }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <form action="{{ route('catalog') }}" method="get" class="relative min-w-0 flex-1" role="search">
                <label for="site-search" class="sr-only">Поиск по каталогу</label>
                <input id="site-search" type="search" name="q" value="{{ request()->routeIs('catalog*') ? request('q') : '' }}"
                       placeholder="Ноутбук, iPhone, RTX 4070…"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-4 pr-11 text-sm placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-100">
                <button type="submit" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-slate-500 hover:text-brand-600" aria-label="Найти">
                    <x-shop-icon name="search" class="size-5" />
                </button>
            </form>

            <nav class="flex shrink-0 items-center gap-1">
                @auth
                    <a href="{{ route('account') }}" class="flex flex-col items-center rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 hover:text-brand-600" title="Личный кабинет">
                        <x-shop-icon name="user" class="size-6" />
                        <span class="hidden md:block">Кабинет</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="flex flex-col items-center rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 hover:text-brand-600" title="Войти">
                        <x-shop-icon name="user" class="size-6" />
                        <span class="hidden md:block">Войти</span>
                    </a>
                @endauth
                <livewire:cart-counter />
            </nav>
        </div>

        <nav class="hidden border-t border-slate-100 md:block" aria-label="Категории">
            <div class="mx-auto flex max-w-7xl items-center gap-1 overflow-x-auto px-4 py-1.5 text-sm">
                @foreach ($navCategories as $root)
                    @foreach ($root->children as $child)
                        <a href="{{ route('catalog.category', $child) }}"
                           @class([
                               'whitespace-nowrap rounded-lg px-3 py-1.5 font-medium transition',
                               'bg-brand-50 text-brand-700' => request()->is('catalog/'.$child->slug),
                               'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->is('catalog/'.$child->slug),
                           ])>{{ $child->name }}</a>
                    @endforeach
                @endforeach
            </div>
        </nav>
    </header>

    <main class="flex-1">
        @if (session('error'))
            <div class="mx-auto mt-6 max-w-7xl px-4">
                <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <x-shop-icon name="warning" class="mt-0.5 size-5 shrink-0" /> {{ session('error') }}
                </div>
            </div>
        @endif
        @if (session('status'))
            <div class="mx-auto mt-6 max-w-7xl px-4">
                <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    <x-shop-icon name="check-circle" class="mt-0.5 size-5 shrink-0" /> {{ session('status') }}
                </div>
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="mt-16 bg-slate-900 text-sm text-slate-400">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <div class="flex items-center gap-2 text-lg font-extrabold text-white">
                    <span class="grid size-8 place-items-center rounded-lg bg-brand-600 text-volt-400"><x-shop-icon name="bolt" class="size-5" stroke-width="2" /></span>
                    {{ config('shop.name') }}
                </div>
                <p class="mt-3 leading-relaxed">Компьютеры, ноутбуки и смартфоны с официальной гарантией. Демо-магазин: оплата в тестовом режиме ЮKassa.</p>
            </div>
            <div>
                <div class="font-bold text-white">Каталог</div>
                <ul class="mt-3 space-y-2">
                    @foreach ($navCategories as $root)
                        <li><a href="{{ route('catalog.category', $root) }}" class="hover:text-white">{{ $root->name }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <div class="font-bold text-white">Покупателям</div>
                <ul class="mt-3 space-y-2">
                    <li>Курьером — {{ money_rub(config('shop.delivery.courier_price')) }}, от {{ money_rub(config('shop.delivery.free_from')) }} бесплатно</li>
                    <li>Самовывоз — бесплатно</li>
                    <li>Оплата картой онлайн через ЮKassa</li>
                </ul>
            </div>
            <div>
                <div class="font-bold text-white">Контакты</div>
                <ul class="mt-3 space-y-2">
                    <li>{{ config('shop.phone') }}</li>
                    <li>{{ config('shop.email') }}</li>
                    <li>{{ config('shop.delivery.pickup_address') }}</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-slate-800 py-4 text-center text-xs">© {{ now()->year }} {{ config('shop.name') }}. Портфолио-проект на Laravel.</div>
    </footer>

    {{-- Всплывающие уведомления: $this->dispatch('toast', message: ..., type: 'success'|'error') --}}
    <div x-data="{ toasts: [] }"
         @toast.window="const id = Date.now() + Math.random(); toasts.push({ id, ...$event.detail }); setTimeout(() => toasts = toasts.filter(t => t.id !== id), 3500)"
         class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex flex-col items-center gap-2 px-4 sm:bottom-6 sm:right-6 sm:left-auto sm:items-end"
         aria-live="polite">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition class="pointer-events-auto flex max-w-sm items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-lg"
                 :class="toast.type === 'error' ? 'bg-rose-600' : 'bg-slate-900'">
                <span x-text="toast.message"></span>
                <a x-show="toast.type !== 'error'" href="{{ route('cart') }}" class="whitespace-nowrap text-volt-300 hover:text-volt-400">В корзину →</a>
            </div>
        </template>
    </div>

    @livewireScripts
</body>
</html>
