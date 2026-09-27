<?php

use Illuminate\Support\Facades\Schedule;

// Неоплаченные заказы с истёкшим резервом отменяются, товар возвращается на склад.
Schedule::command('shop:expire-orders')->everyFiveMinutes()->withoutOverlapping();

// На виртуальном хостинге нет постоянных процессов: очередь разбирает cron через планировщик.
if (config('shop.scheduled_queue_worker')) {
    Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
        ->everyMinute()
        ->withoutOverlapping(5);
}
