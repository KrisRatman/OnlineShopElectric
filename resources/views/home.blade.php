<x-layout>
    {{-- Баннер --}}
    <section class="relative overflow-hidden bg-brand-700">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(255,210,31,0.25),transparent_45%),radial-gradient(circle_at_10%_90%,rgba(89,141,255,0.5),transparent_50%)]"></div>
        <div class="relative mx-auto grid max-w-7xl items-center gap-8 px-4 py-12 md:grid-cols-2 md:py-16">
            <div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-wider text-volt-300 ring-1 ring-white/20">
                    <x-shop-icon name="bolt" class="size-4" /> Заряжено скидками
                </span>
                <h1 class="mt-4 text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl">
                    Техника, которая<br><span class="text-volt-400">работает на вас</span>
                </h1>
                <p class="mt-4 max-w-md text-lg text-brand-100">
                    Игровые ПК, ноутбуки и смартфоны с официальной гарантией. Резервируем товар при оформлении, оплата картой онлайн.
                </p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="{{ route('catalog') }}" class="rounded-xl bg-volt-400 px-6 py-3 font-bold text-slate-900 shadow-lg shadow-volt-500/30 transition hover:bg-volt-300">Перейти в каталог</a>
                    <a href="{{ route('catalog', ['sort' => 'price_asc', 'stock' => 1]) }}" class="rounded-xl bg-white/10 px-6 py-3 font-bold text-white ring-1 ring-white/25 transition hover:bg-white/20">Сначала дешёвые</a>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-3 sm:gap-4">
                @foreach ($categories as $category)
                    <a href="{{ route('catalog.category', $category) }}"
                       class="group flex aspect-[3/4] flex-col items-center justify-center gap-3 rounded-2xl bg-white/10 p-3 text-center text-white ring-1 ring-white/15 backdrop-blur transition hover:-translate-y-1 hover:bg-white/15">
                        <span class="grid size-16 place-items-center rounded-2xl bg-white text-brand-600 shadow-lg transition group-hover:bg-volt-400 group-hover:text-slate-900 sm:size-20">
                            <x-shop-icon :name="$category->icon()" class="size-9 sm:size-11" />
                        </span>
                        <span class="text-sm font-bold sm:text-base">{{ $category->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Преимущества --}}
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['truck', 'Доставка за 1–2 дня', 'Бесплатно от '.money_rub(config('shop.delivery.free_from'))],
                ['shield', 'Официальная гарантия', 'От 1 года на всю технику'],
                ['card', 'Оплата онлайн', 'Картой через ЮKassa'],
                ['refresh', 'Возврат 14 дней', 'Без вопросов и лишних бумаг'],
            ] as [$icon, $title, $text])
                <div class="flex items-center gap-3">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600"><x-shop-icon :name="$icon" class="size-6" /></span>
                    <div>
                        <div class="font-bold text-slate-900">{{ $title }}</div>
                        <div class="text-sm text-slate-500">{{ $text }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="mx-auto max-w-7xl space-y-14 px-4 py-12">
        {{-- Популярные категории --}}
        <section>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Популярные категории</h2>
            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($categories as $root)
                    @foreach ($root->children as $child)
                        <a href="{{ route('catalog.category', $child) }}" class="group flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 font-semibold text-slate-700 transition hover:border-brand-300 hover:text-brand-700">
                            <span class="flex items-center gap-2.5">
                                <x-shop-icon :name="$root->icon()" class="size-5 text-slate-400 group-hover:text-brand-600" />
                                {{ $child->name }}
                            </span>
                            <x-shop-icon name="chevron-right" class="size-4 text-slate-300 group-hover:text-brand-500" />
                        </a>
                    @endforeach
                @endforeach
            </div>
        </section>

        @if ($featured->isNotEmpty())
            <section>
                <div class="flex items-end justify-between gap-4">
                    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Хиты продаж</h2>
                    <a href="{{ route('catalog') }}" class="text-sm font-bold text-brand-600 hover:text-brand-700">Весь каталог →</a>
                </div>
                <div class="mt-5 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($featured as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </section>
        @endif

        @if ($latest->isNotEmpty())
            <section>
                <div class="flex items-end justify-between gap-4">
                    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Новинки</h2>
                    <a href="{{ route('catalog', ['sort' => 'new']) }}" class="text-sm font-bold text-brand-600 hover:text-brand-700">Все новинки →</a>
                </div>
                <div class="mt-5 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($latest as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layout>
