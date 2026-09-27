@props(['product'])

@php($discount = $product->discountPercent())

<article {{ $attributes->merge(['class' => 'group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-lg hover:shadow-brand-900/5']) }} data-product="{{ $product->slug }}">
    <a href="{{ route('products.show', $product) }}" class="relative block aspect-[4/3] bg-gradient-to-b from-slate-50 to-white p-4">
        <x-product-image :product="$product" class="size-full object-contain transition duration-300 group-hover:scale-[1.03]" />
        <div class="absolute left-3 top-3 flex flex-col items-start gap-1">
            @if ($discount)
                <span class="rounded-md bg-rose-500 px-2 py-0.5 text-xs font-bold text-white">−{{ $discount }}%</span>
            @endif
            @if ($product->is_featured)
                <span class="rounded-md bg-volt-400 px-2 py-0.5 text-xs font-bold text-slate-900">Хит</span>
            @endif
        </div>
    </a>

    <div class="flex flex-1 flex-col gap-3 p-4 pt-2">
        <div class="flex-1">
            @if ($product->brand)
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $product->brand->name }}</div>
            @endif
            <h3 class="mt-1 line-clamp-2 font-semibold leading-snug text-slate-900">
                <a href="{{ route('products.show', $product) }}" class="hover:text-brand-600">{{ $product->name }}</a>
            </h3>
        </div>

        <div>
            <div class="flex flex-wrap items-baseline gap-x-2">
                <span class="text-xl font-extrabold text-slate-900">{{ money_rub($product->price) }}</span>
                @if ($product->old_price && $product->old_price > $product->price)
                    <span class="text-sm text-slate-400 line-through">{{ money_rub($product->old_price) }}</span>
                @endif
            </div>
            <x-stock-label :product="$product" class="mt-1" />
        </div>

        <livewire:add-to-cart :product="$product" :key="'card-'.$product->id" />
    </div>
</article>
