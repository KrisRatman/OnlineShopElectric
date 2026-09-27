@php
    $crumbs = [];
    if ($category) {
        $crumbs[] = ['Каталог', route('catalog')];
        if ($category->parent) {
            $crumbs[] = [$category->parent->name, route('catalog.category', $category->parent)];
        }
        $crumbs[] = [$category->name, null];
    } else {
        $crumbs[] = ['Каталог', null];
    }
    $products = $this->products;
    $range = $this->priceRange;
@endphp

<div class="mx-auto max-w-7xl px-4 py-6" x-data="{ filters: false }">
    <x-breadcrumbs :items="$crumbs" />

    <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ $this->title() }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ trans_choice(':count товар|:count товара|:count товаров', $products->total(), ['count' => $products->total()]) }}</p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="filters = !filters" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 lg:hidden">
                <x-shop-icon name="filters" class="size-5" /> Фильтры
                @if ($this->hasFilters())<span class="size-2 rounded-full bg-brand-600"></span>@endif
            </button>
            <label for="sort" class="sr-only">Сортировка</label>
            <select id="sort" wire:model.live="sort" class="rounded-xl border border-slate-200 bg-white py-2.5 pl-3.5 pr-9 text-sm font-semibold text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-100">
                @foreach (\App\Livewire\Catalog::SORTS as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Подкатегории --}}
    @if ($category && $category->children->isNotEmpty())
        <div class="mt-5 flex flex-wrap gap-2">
            @foreach ($category->children as $child)
                <a href="{{ route('catalog.category', $child) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-brand-300 hover:text-brand-700">{{ $child->name }}</a>
            @endforeach
        </div>
    @elseif (! $category && $roots->isNotEmpty() && $q === '')
        <div class="mt-5 flex flex-wrap gap-2">
            @foreach ($roots as $root)
                <a href="{{ route('catalog.category', $root) }}" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-brand-300 hover:text-brand-700">
                    <x-shop-icon :name="$root->icon()" class="size-4 text-brand-600" /> {{ $root->name }}
                </a>
            @endforeach
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-[16rem_1fr]">
        {{-- Фильтры --}}
        <aside class="lg:block" :class="filters ? 'block' : 'hidden'" data-filters>
            <div class="space-y-6 rounded-2xl border border-slate-200 bg-white p-5 lg:sticky lg:top-36">
                @if ($q !== '')
                    <div>
                        <div class="text-sm font-bold text-slate-900">Поиск</div>
                        <div class="mt-2 flex items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm">
                            <span class="truncate">«{{ $q }}»</span>
                            <button type="button" wire:click="$set('q', '')" class="text-slate-400 hover:text-rose-500" aria-label="Сбросить поиск"><x-shop-icon name="x" class="size-4" /></button>
                        </div>
                    </div>
                @endif

                <div>
                    <div class="text-sm font-bold text-slate-900">Цена, ₽</div>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <input type="number" min="0" wire:model.live.debounce.700ms="priceFrom" placeholder="от {{ number_format($range['min'], 0, '', ' ') }}" aria-label="Цена от"
                               class="no-spin w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        <input type="number" min="0" wire:model.live.debounce.700ms="priceTo" placeholder="до {{ number_format($range['max'], 0, '', ' ') }}" aria-label="Цена до"
                               class="no-spin w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                    </div>
                </div>

                <label class="flex cursor-pointer items-center justify-between gap-3 text-sm font-semibold text-slate-800">
                    Только в наличии
                    <input type="checkbox" wire:model.live="inStock" class="size-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                </label>

                @if ($this->brandOptions->count() > 1)
                    <div>
                        <div class="text-sm font-bold text-slate-900">Бренд</div>
                        <div class="mt-2 space-y-1.5">
                            @foreach ($this->brandOptions as $brand)
                                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-700 hover:text-slate-900">
                                    <input type="checkbox" wire:model.live="brands" value="{{ $brand->slug }}" class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                    {{ $brand->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                @foreach ($this->attributeOptions as $option)
                    <div wire:key="attr-{{ $option['attribute']->id }}">
                        <div class="text-sm font-bold text-slate-900">{{ $option['attribute']->name }}</div>
                        @php($selected = (array) ($attrs[$option['attribute']->slug] ?? []))
                        {{-- Длинные списки сворачиваем, но выбранное значение всегда на виду. --}}
                        <div class="mt-2 space-y-1.5" x-data="{ more: false }">
                            @foreach ($option['values'] as $value)
                                @php($collapsed = $loop->index >= 6 && ! in_array($value, $selected, true))
                                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-700 hover:text-slate-900" @if ($collapsed) x-show="more" x-cloak @endif>
                                    <input type="checkbox" wire:model.live="attrs.{{ $option['attribute']->slug }}" value="{{ $value }}" class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                    {{ $value }}
                                </label>
                            @endforeach
                            @if (count($option['values']) > 6)
                                <button type="button" @click="more = !more" class="text-sm font-semibold text-brand-600 hover:text-brand-700"
                                        x-text="more ? 'Свернуть' : 'Показать все ({{ count($option['values']) }})'"></button>
                            @endif
                        </div>
                    </div>
                @endforeach

                @if ($this->hasFilters())
                    <button type="button" wire:click="resetFilters" class="w-full rounded-xl border border-slate-200 py-2 text-sm font-semibold text-slate-600 hover:border-rose-200 hover:text-rose-600">
                        Сбросить фильтры
                    </button>
                @endif
            </div>
        </aside>

        {{-- Товары --}}
        <div>
            <div wire:loading.class="opacity-50" class="transition-opacity">
                @if ($products->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                        <x-shop-icon name="search" class="mx-auto size-10 text-slate-300" />
                        <p class="mt-3 font-semibold text-slate-700">Ничего не нашлось</p>
                        <p class="mt-1 text-sm text-slate-500">Попробуйте изменить запрос или сбросить фильтры.</p>
                        @if ($this->hasFilters())
                            <button type="button" wire:click="resetFilters" class="mt-4 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-700">Сбросить фильтры</button>
                        @endif
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3">
                        @foreach ($products as $product)
                            <x-product-card :product="$product" wire:key="product-{{ $product->id }}" />
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</div>
