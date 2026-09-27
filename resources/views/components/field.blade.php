@props(['label', 'for', 'error' => null, 'hint' => null])

<div {{ $attributes }}>
    <label for="{{ $for }}" class="block text-sm font-semibold text-slate-700">{{ $label }}</label>
    <div class="mt-1.5">{{ $slot }}</div>
    @error($error ?? $for)
        <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-xs text-slate-400">{{ $hint }}</p>
    @enderror
</div>
