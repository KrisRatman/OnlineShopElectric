<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Тестовая оплата — {{ config('shop.name') }}</title>
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-full items-center justify-center bg-slate-100 px-4 py-10 font-sans text-slate-800 antialiased">
    <div class="w-full max-w-md">
        <div class="mb-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <x-shop-icon name="warning" class="mt-0.5 size-5 shrink-0" />
            <span><b>Демо-режим.</b> Магазин подключается к ЮKassa, пока вместо её формы — эта страница. Деньги не списываются, карту вводить не нужно.</span>
        </div>

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-900/5">
            <div class="border-b border-slate-100 px-6 py-5">
                <div class="text-sm text-slate-500">{{ $payment['description'] }}</div>
                <div class="mt-1 text-3xl font-extrabold text-slate-900" data-demo-amount>{{ money_rub($amount) }}</div>
            </div>

            <div class="space-y-3 px-6 py-5">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-sm tracking-widest text-slate-400">5555 5555 5555 4477</div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-sm text-slate-400">12 / 30</div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-sm text-slate-400">CVC •••</div>
                </div>
            </div>

            <form method="post" action="{{ route('demo-payment.complete', $payment['id']) }}" class="space-y-3 px-6 pb-6">
                @csrf
                <button type="submit" name="result" value="success"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-6 py-3.5 font-bold text-white hover:bg-brand-700">
                    <x-shop-icon name="card" class="size-5" /> Оплатить {{ money_rub($amount) }}
                </button>
                <button type="submit" name="result" value="fail"
                        class="w-full rounded-xl border border-slate-200 px-6 py-3 text-sm font-semibold text-slate-500 hover:border-rose-200 hover:text-rose-600">
                    Отклонить платёж (проверить неуспешную оплату)
                </button>
            </form>
        </div>

        <p class="mt-4 text-center text-xs text-slate-400">Платёж {{ $payment['id'] }}</p>
    </div>
</body>
</html>
