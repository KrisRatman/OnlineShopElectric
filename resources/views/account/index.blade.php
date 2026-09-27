@php($input = 'w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-100')

<x-layout title="Личный кабинет">
    <div class="mx-auto max-w-7xl px-4 py-6">
        <x-breadcrumbs :items="[['Личный кабинет', null]]" />

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Здравствуйте, {{ $user->name }}!</h1>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:text-rose-600">
                    <x-shop-icon name="logout" class="size-4" /> Выйти
                </button>
            </form>
        </div>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[1fr_20rem]">
            <section>
                <h2 class="text-lg font-bold text-slate-900">Мои заказы</h2>

                @if ($orders->isEmpty())
                    <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                        <x-shop-icon name="box" class="mx-auto size-10 text-slate-300" />
                        <p class="mt-3 font-semibold text-slate-700">Заказов пока нет</p>
                        <a href="{{ route('catalog') }}" class="mt-4 inline-block rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-700">Перейти в каталог</a>
                    </div>
                @else
                    <div class="mt-4 space-y-3">
                        @foreach ($orders as $order)
                            <a href="{{ route('orders.show', $order->token) }}" class="block rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-brand-300 hover:shadow-sm" data-account-order="{{ $order->number }}">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <div class="font-bold text-slate-900">Заказ №{{ $order->number }}</div>
                                        <div class="text-sm text-slate-500">{{ $order->created_at->timezone(config('shop.timezone'))->isoFormat('D MMMM YYYY') }} · {{ trans_choice(':count товар|:count товара|:count товаров', $order->itemsCount(), ['count' => $order->itemsCount()]) }}</div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $order->status->badgeClasses() }}">{{ $order->status->getLabel() }}</span>
                                        <span class="text-lg font-extrabold text-slate-900">{{ money_rub($order->total) }}</span>
                                    </div>
                                </div>
                                <div class="mt-3 line-clamp-1 text-sm text-slate-500">{{ $order->items->pluck('product_name')->join(', ') }}</div>
                            </a>
                        @endforeach
                    </div>
                    <div class="mt-6">{{ $orders->links() }}</div>
                @endif
            </section>

            <aside class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="font-bold text-slate-900">Профиль</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $user->email }}</p>
                <form method="post" action="{{ route('account.profile') }}" class="mt-4 space-y-4">
                    @csrf
                    @method('put')
                    <x-field label="Имя" for="name">
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" class="{{ $input }}" required>
                    </x-field>
                    <x-field label="Телефон" for="phone" hint="Подставим при оформлении заказа">
                        <input id="phone" name="phone" type="tel" value="{{ old('phone', \App\Support\Phone::format($user->phone)) }}" class="{{ $input }}" placeholder="+7 900 000-00-00">
                    </x-field>
                    <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800">Сохранить</button>
                </form>
                @if ($user->is_admin)
                    <a href="{{ url('/admin') }}" class="mt-4 block rounded-xl border border-brand-200 bg-brand-50 px-4 py-2.5 text-center text-sm font-bold text-brand-700 hover:bg-brand-100">Открыть админку</a>
                @endif
            </aside>
        </div>
    </div>
</x-layout>
