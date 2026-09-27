<x-mail::message>
# Заказ №{{ $order->number }}

@foreach ($intro as $line)
{{ $line }}

@endforeach
<x-mail::table>
| Товар | Кол-во | Сумма |
|:------|:------:|------:|
@foreach ($order->items as $item)
| {{ $item->product_name }} | {{ $item->quantity }} | {{ money_rub($item->total) }} |
@endforeach
| Доставка: {{ mb_strtolower($order->delivery_method->getLabel()) }} | | {{ $order->delivery_price ? money_rub($order->delivery_price) : 'бесплатно' }} |
| **Итого** | | **{{ money_rub($order->total) }}** |
</x-mail::table>

@if ($order->delivery_address)
**Адрес доставки:** {{ $order->delivery_address }}
@else
**Самовывоз:** {{ config('shop.delivery.pickup_address') }}
@endif

<x-mail::button :url="$actionUrl">
{{ $actionText }}
</x-mail::button>

Вопросы по заказу — {{ config('shop.phone') }} или {{ config('shop.email') }}.

{{ config('shop.name') }}
</x-mail::message>
