<?php

use App\Livewire\AddToCart;
use App\Livewire\CartPage;
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartException;
use App\Services\Cart\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Livewire\Livewire;

/** Корзина гостя с данным токеном в cookie (или без cookie). */
function guestCart(?string $token = null): CartService
{
    $request = Request::create('/', 'GET', cookies: $token ? [CartService::COOKIE => $token] : []);

    return new CartService($request, app('auth'));
}

it('создаёт гостевую корзину при первом добавлении и запоминает её в cookie', function () {
    $product = Product::factory()->create();

    expect(Cart::count())->toBe(0);

    guestCart()->add($product);

    $cart = Cart::sole();
    expect($cart->user_id)->toBeNull()
        ->and($cart->token)->toHaveLength(40)
        ->and($cart->items()->sole()->quantity)->toBe(1)
        ->and(Cookie::queued(CartService::COOKIE)->getValue())->toBe($cart->token);
});

it('по cookie находит ту же корзину', function () {
    $product = Product::factory()->create();
    guestCart()->add($product);
    $token = Cart::sole()->token;

    expect(guestCart($token)->count())->toBe(1)
        ->and(guestCart('чужой-токен')->count())->toBe(0);
});

it('повторное добавление увеличивает количество, но не больше остатка', function () {
    $product = Product::factory()->stock(3)->create();
    $cart = guestCart();

    expect($cart->add($product))->toBe(1)
        ->and($cart->add($product, 5))->toBe(3);

    $cart->add($product);
})->throws(CartException::class, 'доступно 3 шт.');

it('ограничивает количество одного товара настройкой max_quantity', function () {
    config(['shop.max_quantity' => 2]);
    $product = Product::factory()->stock(50)->create();

    expect(guestCart()->add($product, 7))->toBe(2);
});

it('не кладёт в корзину товар, которого нет в наличии или который скрыт', function (Product $product) {
    guestCart()->add($product);
})->with([
    'закончился' => fn () => Product::factory()->stock(0)->create(),
    'снят с продажи' => fn () => Product::factory()->inactive()->create(),
])->throws(CartException::class, 'нет в наличии');

it('меняет количество в пределах остатка, а 0 убирает товар', function () {
    $product = Product::factory()->stock(4)->create();
    $cart = guestCart();
    $cart->add($product);

    $cart->setQuantity($product->id, 99);
    expect($cart->quantityOf($product))->toBe(4);

    $cart->setQuantity($product->id, 0);
    expect($cart->quantityOf($product))->toBe(0);
});

it('если товара стало меньше, уменьшает количество и объясняет почему', function () {
    $product = Product::factory()->stock(5)->create();
    $cart = guestCart();
    $cart->add($product, 5);

    $product->update(['stock' => 2]);
    $line = $cart->summary()->lines[0];

    expect($line->quantity)->toBe(2)
        ->and($line->notice)->toContain('осталось 2 шт.')
        ->and($cart->quantityOf($product))->toBe(2);
});

it('закончившийся товар остаётся в корзине, но не идёт в сумму', function () {
    $available = Product::factory()->price(1000)->create();
    $gone = Product::factory()->price(5000)->create();
    $cart = guestCart();
    $cart->add($available);
    $cart->add($gone);

    $gone->update(['stock' => 0]);
    $summary = $cart->summary();

    expect($summary->lines)->toHaveCount(2)
        ->and($summary->subtotal())->toBe(100000)
        ->and($cart->count())->toBe(1);
});

it('у авторизованного корзина привязана к аккаунту', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user);
    guestCart()->add($product);

    expect(Cart::sole()->user_id)->toBe($user->id)
        ->and(Cart::sole()->token)->toBeNull();
});

it('при входе переносит гостевую корзину в аккаунт и складывает количества', function () {
    $user = User::factory()->create(['email' => 'anna@example.com']);
    $both = Product::factory()->stock(3)->create();
    $guestOnly = Product::factory()->create();

    $userCart = Cart::create(['user_id' => $user->id]);
    $userCart->items()->create(['product_id' => $both->id, 'quantity' => 2]);

    $guest = Cart::create(['token' => 'guest-token']);
    $guest->items()->create(['product_id' => $both->id, 'quantity' => 2]);
    $guest->items()->create(['product_id' => $guestOnly->id, 'quantity' => 1]);

    $this->withCookie(CartService::COOKIE, 'guest-token')
        ->post('/login', ['email' => 'anna@example.com', 'password' => 'password'])
        ->assertRedirect(route('account'));

    expect(Cart::count())->toBe(1)
        ->and($userCart->items()->pluck('quantity', 'product_id')->all())
        ->toBe([$both->id => 3, $guestOnly->id => 1]);
});

it('кнопка «В корзину» добавляет товар и обновляет счётчик в шапке', function () {
    $product = Product::factory()->create();

    Livewire::test(AddToCart::class, ['product' => $product])
        ->call('add')
        ->assertSet('inCart', 1)
        ->assertDispatched('cart-updated')
        ->assertDispatched('toast');

    expect(Cart::sole()->items()->sole()->product_id)->toBe($product->id);
});

it('кнопка показывает ошибку, если товар закончился, пока страница была открыта', function () {
    $product = Product::factory()->stock(1)->create();
    $component = Livewire::test(AddToCart::class, ['product' => $product]);

    $product->update(['stock' => 0]);

    $component->call('add')
        ->assertSet('max', 0)
        ->assertDispatched('toast', type: 'error');
});

it('страница корзины показывает позиции и меняет количество', function () {
    $user = User::factory()->create();
    $product = Product::factory()->price(1500)->stock(5)->create(['name' => 'Тестовый смартфон']);
    $cart = Cart::create(['user_id' => $user->id]);
    $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

    $this->actingAs($user);

    Livewire::test(CartPage::class)
        ->assertSee('Тестовый смартфон')
        ->assertSee('1 500 ₽')
        ->call('setQuantity', $product->id, 3)
        ->assertSee('4 500 ₽')
        ->assertDispatched('cart-updated')
        ->call('remove', $product->id)
        ->assertSee('В корзине пока пусто');
});
