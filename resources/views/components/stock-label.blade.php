@props(['product'])

@if ($product->stock < 1)
    <div {{ $attributes->merge(['class' => 'text-sm font-medium text-slate-400']) }}>Нет в наличии</div>
@elseif ($product->stock <= config('shop.low_stock'))
    <div {{ $attributes->merge(['class' => 'flex items-center gap-1.5 text-sm font-medium text-amber-600']) }}>
        <span class="size-1.5 rounded-full bg-amber-500"></span> Осталось {{ $product->stock }} шт.
    </div>
@else
    <div {{ $attributes->merge(['class' => 'flex items-center gap-1.5 text-sm font-medium text-emerald-600']) }}>
        <span class="size-1.5 rounded-full bg-emerald-500"></span> В наличии
    </div>
@endif
