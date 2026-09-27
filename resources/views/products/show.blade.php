<x-layout :title="$product->name">
    @php
        $crumbs = [['Каталог', route('catalog')]];
        if ($product->category->parent) {
            $crumbs[] = [$product->category->parent->name, route('catalog.category', $product->category->parent)];
        }
        $crumbs[] = [$product->category->name, route('catalog.category', $product->category)];
        $crumbs[] = [$product->name, null];
        $images = $product->images->map(fn ($image) => $image->url())->values();
        $discount = $product->discountPercent();
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-6">
        <x-breadcrumbs :items="$crumbs" />

        <div class="mt-6 grid gap-8 lg:grid-cols-2 lg:gap-12">
            {{-- Галерея --}}
            <div x-data="{ current: 0, images: @js($images) }">
                <div class="relative aspect-square overflow-hidden rounded-3xl border border-slate-200 bg-white p-8">
                    @if ($images->isEmpty())
                        <x-product-image :product="$product" class="size-full" />
                    @else
                        <img :src="images[current]" src="{{ $images->first() }}" alt="{{ $product->name }}" class="size-full object-contain">
                    @endif
                    @if ($discount)
                        <span class="absolute left-4 top-4 rounded-lg bg-rose-500 px-2.5 py-1 text-sm font-bold text-white">−{{ $discount }}%</span>
                    @endif
                </div>
                @if ($images->count() > 1)
                    <div class="mt-3 flex gap-3">
                        @foreach ($images as $i => $url)
                            <button type="button" @click="current = {{ $i }}"
                                    class="size-20 overflow-hidden rounded-xl border-2 bg-white p-2 transition"
                                    :class="current === {{ $i }} ? 'border-brand-500' : 'border-slate-200 hover:border-slate-300'"
                                    aria-label="Фото {{ $i + 1 }}">
                                <img src="{{ $url }}" alt="" class="size-full object-contain">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Покупка --}}
            <div>
                @if ($product->brand)
                    <a href="{{ route('catalog.category', ['category' => $product->category, 'brand' => [$product->brand->slug]]) }}"
                       class="text-sm font-bold uppercase tracking-wide text-brand-600 hover:text-brand-700">{{ $product->brand->name }}</a>
                @endif
                <h1 class="mt-1 text-2xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-3xl">{{ $product->name }}</h1>
                <div class="mt-2 text-sm text-slate-400">Артикул: {{ $product->sku }}</div>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
                    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                        <span class="text-3xl font-extrabold text-slate-900 sm:text-4xl">{{ money_rub($product->price) }}</span>
                        @if ($product->old_price && $product->old_price > $product->price)
                            <span class="text-lg text-slate-400 line-through">{{ money_rub($product->old_price) }}</span>
                            <span class="rounded-md bg-rose-50 px-2 py-0.5 text-sm font-bold text-rose-600">Экономия {{ money_rub($product->old_price - $product->price) }}</span>
                        @endif
                    </div>
                    <x-stock-label :product="$product" class="mt-2" />

                    <div class="mt-5">
                        <livewire:add-to-cart :product="$product" :with-quantity="true" />
                    </div>

                    <ul class="mt-5 space-y-2 border-t border-slate-100 pt-5 text-sm text-slate-600">
                        <li class="flex items-center gap-2"><x-shop-icon name="truck" class="size-5 text-brand-600" />
                            Курьером — {{ $product->price >= config('shop.delivery.free_from') ? 'бесплатно' : money_rub(config('shop.delivery.courier_price')) }}, 1–2 дня
                        </li>
                        <li class="flex items-center gap-2"><x-shop-icon name="map-pin" class="size-5 text-brand-600" /> Самовывоз бесплатно: {{ config('shop.delivery.pickup_address') }}</li>
                        <li class="flex items-center gap-2"><x-shop-icon name="shield" class="size-5 text-brand-600" /> Официальная гарантия производителя</li>
                    </ul>
                </div>

                @if ($product->attributeValues->isNotEmpty())
                    <div class="mt-6">
                        <h2 class="text-lg font-bold text-slate-900">Основные характеристики</h2>
                        <dl class="mt-3 divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white">
                            @foreach ($product->attributeValues as $value)
                                <div class="flex justify-between gap-4 px-4 py-2.5 text-sm">
                                    <dt class="text-slate-500">{{ $value->attribute->name }}</dt>
                                    <dd class="text-right font-medium text-slate-900">{{ $value->value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
            </div>
        </div>

        @if ($product->description)
            <section class="mt-12 max-w-3xl">
                <h2 class="text-xl font-extrabold text-slate-900">Описание</h2>
                <div class="mt-3 space-y-3 leading-relaxed text-slate-600">
                    @foreach (preg_split('/\n\s*\n/', $product->description) as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($related->isNotEmpty())
            <section class="mt-14">
                <h2 class="text-xl font-extrabold text-slate-900">Похожие товары</h2>
                <div class="mt-5 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($related as $item)
                        <x-product-card :product="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layout>
