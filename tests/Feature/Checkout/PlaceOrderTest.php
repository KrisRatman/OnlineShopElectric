<?php

use App\Actions\CheckoutData;
use App\Actions\PlaceOrder;
use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Livewire\Checkout;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderPlaced;
use App\Services\Cart\CartException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
});

function cartWith(array $items, ?User $user = null): Cart
{
    $cart = Cart::create($user ? ['user_id' => $user->id] : ['token' => 'test-'.uniqid()]);

    foreach ($items as [$product, $quantity]) {
        $cart->items()->create(['product_id' => $product->id, 'quantity' => $quantity]);
    }

    return $cart;
}

function checkoutData(DeliveryMethod $delivery = DeliveryMethod::Courier): CheckoutData
{
    return new CheckoutData('Иван Петров', 'ivan@example.com', '+79161234567', $delivery, 'Москва, ул. Тверская, 1');
}

it('создаёт заказ, фиксирует цены и резервирует товар', function () {
    $laptop = Product::factory()->price(44990)->stock(5)->create(['name' => 'Ноутбук', 'sku' => 'NB-1']);
    $phone = Product::factory()->price(1990)->stock(3)->create(['name' => 'Чехол']);
    $cart = cartWith([[$laptop, 1], [$phone, 2]]);

    $order = app(PlaceOrder::class)->handle($cart, checkoutData());

    expect($order->status)->toBe(OrderStatus::PendingPayment)
        ->and($order->number)->toBe(sprintf('%06d', $order->id))
        ->and($order->subtotal)->toBe(4499000 + 2 * 199000)
        // 48 970 ₽ — меньше порога бесплатной доставки в 50 000 ₽.
        ->and($order->delivery_price)->toBe(49000)
        ->and($order->total)->toBe(4946000)
        ->and($order->payment_due_at->diffInMinutes(now(), true))->toEqualWithDelta(60, 1)
        ->and($order->items)->toHaveCount(2)
        ->and($order->items->firstWhere('product_sku', 'NB-1')->price)->toBe(4499000);

    expect($laptop->fresh()->stock)->toBe(4)
        ->and($phone->fresh()->stock)->toBe(1)
        ->and($cart->items()->count())->toBe(0);

    Notification::assertSentTo($order, OrderPlaced::class);
});

it('добавляет курьерскую доставку ниже порога и не сохраняет адрес для самовывоза', function () {
    $product = Product::factory()->price(10000)->create();

    $courier = app(PlaceOrder::class)->handle(cartWith([[$product, 1]]), checkoutData(DeliveryMethod::Courier));
    $pickup = app(PlaceOrder::class)->handle(cartWith([[$product, 1]]), checkoutData(DeliveryMethod::Pickup));

    expect($courier->delivery_price)->toBe(49000)
        ->and($courier->total)->toBe(1049000)
        ->and($courier->delivery_address)->toBe('Москва, ул. Тверская, 1')
        ->and($pickup->total)->toBe(1000000)
        ->and($pickup->delivery_address)->toBeNull();
});

it('цена в заказе — снимок: поздняя правка цены заказ не меняет', function () {
    $product = Product::factory()->price(1000)->create();
    $order = app(PlaceOrder::class)->handle(cartWith([[$product, 1]]), checkoutData(DeliveryMethod::Pickup));

    $product->update(['price' => 999900, 'name' => 'Переименован']);

    expect($order->items()->sole())
        ->price->toBe(100000)
        ->product_name->not->toBe('Переименован');
});

it('не продаёт больше, чем есть на складе, и ничего не резервирует частично', function () {
    $enough = Product::factory()->stock(5)->create();
    $short = Product::factory()->stock(1)->create(['name' => 'Последний iPhone']);
    $cart = cartWith([[$enough, 2], [$short, 2]]);

    expect(fn () => app(PlaceOrder::class)->handle($cart, checkoutData()))
        ->toThrow(CartException::class, '«Последний iPhone» — осталось 1 шт.');

    expect(Order::count())->toBe(0)
        ->and($enough->fresh()->stock)->toBe(5)
        ->and($cart->items()->count())->toBe(2);
});

it('второй покупатель не может купить уже зарезервированный последний товар', function () {
    $last = Product::factory()->stock(1)->create();
    $first = cartWith([[$last, 1]]);
    $second = cartWith([[$last, 1]]);

    app(PlaceOrder::class)->handle($first, checkoutData());

    expect(fn () => app(PlaceOrder::class)->handle($second, checkoutData()))
        ->toThrow(CartException::class, 'нет в наличии');
    expect($last->fresh()->stock)->toBe(0)
        ->and(Order::count())->toBe(1);
});

it('не оформляет пустую корзину и скрытый товар', function () {
    expect(fn () => app(PlaceOrder::class)->handle(cartWith([]), checkoutData()))
        ->toThrow(CartException::class, 'Корзина пуста');

    $hidden = Product::factory()->inactive()->create();
    expect(fn () => app(PlaceOrder::class)->handle(cartWith([[$hidden, 1]]), checkoutData()))
        ->toThrow(CartException::class, 'нет в наличии');
});

it('оформление на витрине создаёт заказ и отправляет покупателя на оплату в ЮKassa', function () {
    Http::fake(['api.yookassa.ru/*' => fn ($request) => Http::response(yookassaPayment('pay-1', 'pending', 5049000))]);
    $user = User::factory()->create(['name' => 'Анна', 'email' => 'anna@example.com', 'phone' => '+79160000000']);
    $product = Product::factory()->price(50000)->create();
    cartWith([[$product, 1]], $user);
    $this->actingAs($user);

    Livewire::test(Checkout::class)
        ->assertSet('name', 'Анна')
        ->assertSet('phone', '+7 916 000-00-00')
        ->set('address', 'Москва, Арбат, 1')
        ->set('agree', true)
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirect('https://yoomoney.ru/checkout/payments/v2/contract?orderId=pay-1');

    $order = Order::sole();
    expect($order->user_id)->toBe($user->id)
        ->and($order->customer_phone)->toBe('+79160000000')
        ->and($order->latestPayment->provider_payment_id)->toBe('pay-1');
});

it('проверяет форму оформления', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    cartWith([[$product, 1]], $user);
    $this->actingAs($user);

    Livewire::test(Checkout::class)
        ->set('name', '')
        ->set('phone', '123')
        ->set('email', 'не-почта')
        ->set('delivery', 'courier')
        ->set('address', '')
        ->call('placeOrder')
        ->assertHasErrors(['name', 'phone', 'email', 'address', 'agree']);

    expect(Order::count())->toBe(0);
});

it('если ЮKassa недоступна, заказ всё равно сохраняется и его можно оплатить позже', function () {
    Http::fake(['api.yookassa.ru/*' => Http::response(['type' => 'error', 'description' => 'Internal error'], 500)]);
    $product = Product::factory()->create();
    $user = User::factory()->create();
    cartWith([[$product, 1]], $user);
    $this->actingAs($user);

    $component = Livewire::test(Checkout::class)
        ->set('phone', '+7 916 111-22-33')
        ->set('address', 'Москва')
        ->set('agree', true)
        ->call('placeOrder');

    $order = Order::sole();
    $component->assertRedirect(route('orders.show', $order->token));
    expect($order->status)->toBe(OrderStatus::PendingPayment)
        ->and(session('error'))->toContain('временно недоступен');
});

it('с пустой корзиной страница оформления возвращает в корзину', function () {
    Livewire::test(Checkout::class)->assertRedirect(route('cart'));
});
