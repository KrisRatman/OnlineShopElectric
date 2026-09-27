@props(['product', 'src' => null])

@php($url = $src ?? $product->imageUrl())

@if ($url)
    <img src="{{ $url }}" alt="{{ $product->name }}" loading="lazy" {{ $attributes }}>
@else
    <div {{ $attributes->merge(['class' => 'grid place-items-center text-slate-300']) }}>
        <x-shop-icon name="photo" class="size-12" />
    </div>
@endif
