@php($input = 'w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-100')

<x-layout title="Регистрация">
    <div class="mx-auto max-w-md px-4 py-12">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8">
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Регистрация</h1>
            <p class="mt-1 text-sm text-slate-500">Товары из корзины сохранятся в аккаунте.</p>

            <form method="post" action="{{ route('register') }}" class="mt-6 space-y-4">
                @csrf
                <x-field label="Имя" for="name">
                    <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus class="{{ $input }}">
                </x-field>
                <x-field label="Email" for="email">
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required class="{{ $input }}">
                </x-field>
                <x-field label="Пароль" for="password" hint="Не короче 8 символов">
                    <input id="password" name="password" type="password" autocomplete="new-password" required class="{{ $input }}">
                </x-field>
                <x-field label="Пароль ещё раз" for="password_confirmation">
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="{{ $input }}">
                </x-field>
                <button type="submit" class="w-full rounded-xl bg-brand-600 px-6 py-3 font-bold text-white hover:bg-brand-700">Зарегистрироваться</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">Уже есть аккаунт? <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">Войти</a></p>
        </div>
    </div>
</x-layout>
