@php($input = 'w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-100')

<x-layout title="Вход">
    <div class="mx-auto max-w-md px-4 py-12">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8">
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Вход</h1>
            <p class="mt-1 text-sm text-slate-500">Чтобы видеть историю заказов и оформлять быстрее.</p>

            <form method="post" action="{{ route('login') }}" class="mt-6 space-y-4">
                @csrf
                <x-field label="Email" for="email">
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus class="{{ $input }}">
                </x-field>
                <x-field label="Пароль" for="password">
                    <input id="password" name="password" type="password" autocomplete="current-password" required class="{{ $input }}">
                </x-field>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"> Запомнить меня
                </label>
                <button type="submit" class="w-full rounded-xl bg-brand-600 px-6 py-3 font-bold text-white hover:bg-brand-700">Войти</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">Нет аккаунта? <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:text-brand-700">Зарегистрируйтесь</a></p>
        </div>
        @if (config('shop.demo.enabled'))
            <p class="mt-4 text-center text-xs text-slate-400">Демо-покупатель: buyer@example.com / password</p>
        @endif
    </div>
</x-layout>
