<?php

use App\Enums\OrderStatus;
use App\Livewire\OrderPage;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
});

it('открывается по секретной ссылке, а по номеру — нет', function () {
    $product = Product::factory()->create(['name' => 'Apple iPhone 15']);
    $order = Order::factory()->withItem($product)->create();

    $this->get(route('orders.show', $order->token))
        ->assertOk()
        ->assertSee("Заказ №{$order->number}")
        ->assertSee('Apple iPhone 15')
        ->assertSee('Заказ ждёт оплаты');

    $this->get('/orders/'.$order->id)->assertNotFound();
    $this->get('/orders/'.$order->number)->assertNotFound();
});

it('после возврата из ЮKassa проверяет статус и сразу показывает оплату', function () {
    $order = Order::factory()->create();
    createdPayment($order, 'pay-ret');
    Http::fake(['api.yookassa.ru/v3/payments/pay-ret' => Http::response(yookassaPayment('pay-ret', 'succeeded', $order->total))]);

    $this->get(route('orders.payment-return', $order->token))
        ->assertRedirect(route('orders.show', ['order' => $order->token, 'returned' => 1]));

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);

    $this->get(route('orders.show', $order->token))->assertSee('Оплата получена');
});

it('пока ЮKassa не подтвердила оплату, страница ждёт и опрашивает статус', function () {
    $order = Order::factory()->create();
    createdPayment($order, 'pay-wait');
    Http::fakeSequence('api.yookassa.ru/*')
        ->push(yookassaPayment('pay-wait', 'pending', $order->total))
        ->push(yookassaPayment('pay-wait', 'succeeded', $order->total));

    Livewire::withQueryParams(['returned' => 1])
        ->test(OrderPage::class, ['order' => $order])
        ->assertSee('Проверяем оплату')
        ->call('checkPayment')
        ->assertSee('Проверяем оплату')
        ->call('checkPayment')
        ->assertSee('Оплата получена');
});

it('кнопка «Оплатить» ведёт на страницу ЮKassa', function () {
    Http::fake(['api.yookassa.ru/*' => Http::response(yookassaPayment('pay-btn', 'pending', 5000000))]);
    $order = Order::factory()->create();

    Livewire::test(OrderPage::class, ['order' => $order])
        ->call('pay')
        ->assertRedirect('https://yoomoney.ru/checkout/payments/v2/contract?orderId=pay-btn');
});

it('у отменённого заказа нет кнопки оплаты', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Cancelled, 'cancel_reason' => 'Истёк срок оплаты']);

    $this->get(route('orders.show', $order->token))
        ->assertSee('Заказ отменён')
        ->assertSee('Истёк срок оплаты')
        ->assertDontSee('Оплатить');
});
