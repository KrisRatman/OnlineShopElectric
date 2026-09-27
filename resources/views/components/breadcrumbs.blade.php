@props(['items' => []])

{{-- $items: [['Название', 'url' | null], ...] --}}
<nav aria-label="Навигация" {{ $attributes->merge(['class' => 'text-sm text-slate-500']) }}>
    <ol class="flex flex-wrap items-center gap-1.5">
        <li><a href="{{ route('home') }}" class="hover:text-brand-600">Главная</a></li>
        @foreach ($items as [$label, $url])
            <li class="flex items-center gap-1.5">
                <x-shop-icon name="chevron-right" class="size-3.5 text-slate-300" />
                @if ($url && ! $loop->last)
                    <a href="{{ $url }}" class="hover:text-brand-600">{{ $label }}</a>
                @else
                    <span class="text-slate-700">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
