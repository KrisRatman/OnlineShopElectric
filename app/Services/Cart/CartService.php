<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Корзина текущего посетителя.
 *
 * Авторизованному — корзина по user_id, гостю — по случайному токену в cookie.
 * Гостевая корзина создаётся лениво, при первом добавлении товара, чтобы не плодить
 * пустые записи на каждого бота. Сервис живёт один запрос (scoped).
 */
class CartService
{
    public const COOKIE = 'cart_token';

    private const COOKIE_MINUTES = 60 * 24 * 30;

    private ?Cart $cart = null;

    private bool $resolved = false;

    public function __construct(
        private readonly Request $request,
        private readonly AuthFactory $auth,
    ) {}

    public static function maxQuantityFor(Product $product): int
    {
        return $product->is_active ? min($product->stock, (int) config('shop.max_quantity')) : 0;
    }

    public function cart(): ?Cart
    {
        if (! $this->resolved) {
            $this->cart = $this->findCart();
            $this->resolved = true;
        }

        return $this->cart;
    }

    /**
     * Положить товар в корзину. Если он уже там — увеличить количество.
     *
     * @return int сколько штук этого товара теперь в корзине
     */
    public function add(Product $product, int $quantity = 1): int
    {
        $max = self::maxQuantityFor($product);

        if ($max < 1) {
            throw new CartException('Товара нет в наличии.');
        }

        $cart = $this->cartOrCreate();
        $item = $cart->items()->firstOrNew(['product_id' => $product->id]);
        $wanted = ($item->exists ? $item->quantity : 0) + max(1, $quantity);

        if ($item->exists && $item->quantity >= $max) {
            throw new CartException("Больше добавить нельзя: доступно {$max} шт.");
        }

        $item->quantity = min($wanted, $max);
        $item->save();
        $cart->touch();

        return $item->quantity;
    }

    /** Поменять количество. 0 — убрать из корзины. */
    public function setQuantity(int $productId, int $quantity): void
    {
        $item = $this->findItem($productId);

        if ($item === null) {
            return;
        }

        if ($quantity < 1) {
            $item->delete();

            return;
        }

        $item->update(['quantity' => max(1, min($quantity, self::maxQuantityFor($item->product)))]);
    }

    public function remove(int $productId): void
    {
        $this->findItem($productId)?->delete();
    }

    public function clear(): void
    {
        $this->cart()?->items()->delete();
    }

    /**
     * Расчёт корзины. Заодно приводит её в порядок: если товара стало меньше,
     * чем лежит в корзине, количество уменьшается, а покупатель видит пояснение.
     */
    public function summary(): CartSummary
    {
        $cart = $this->cart();

        if ($cart === null) {
            return new CartSummary([]);
        }

        $items = $cart->items()->with(['product.mainImage', 'product.category'])->get();
        $lines = [];

        foreach ($items as $item) {
            $product = $item->product;
            $max = self::maxQuantityFor($product);

            if ($max < 1) {
                $lines[] = new CartLine($product, $item->quantity, unavailable: true, notice: 'Нет в наличии');

                continue;
            }

            $notice = null;

            if ($item->quantity > $max) {
                $item->update(['quantity' => $max]);
                $notice = "На складе осталось {$max} шт. — количество уменьшено.";
            }

            $lines[] = new CartLine($product, $item->quantity, notice: $notice);
        }

        return new CartSummary($lines);
    }

    public function count(): int
    {
        $cart = $this->cart();

        if ($cart === null) {
            return 0;
        }

        return (int) $cart->items()
            ->whereHas('product', fn ($q) => $q->active()->where('stock', '>', 0))
            ->sum('quantity');
    }

    /** Сколько штук этого товара уже в корзине. */
    public function quantityOf(Product $product): int
    {
        return (int) $this->cart()?->items()->where('product_id', $product->id)->value('quantity');
    }

    /**
     * Покупатель вошёл: товары гостевой корзины переезжают в его корзину.
     * Одинаковые товары складываются, но не больше остатка.
     */
    public function mergeGuestCartInto(User $user): void
    {
        $token = $this->request->cookie(self::COOKIE);

        if (! is_string($token) || $token === '') {
            return;
        }

        $guest = Cart::query()->whereNull('user_id')->where('token', $token)->first();

        if ($guest !== null) {
            DB::transaction(function () use ($guest, $user): void {
                $target = Cart::query()->firstOrCreate(['user_id' => $user->id]);

                foreach ($guest->items()->with('product')->get() as $item) {
                    $existing = $target->items()->firstOrNew(['product_id' => $item->product_id]);
                    $quantity = ($existing->exists ? $existing->quantity : 0) + $item->quantity;
                    $existing->quantity = max(1, min($quantity, max(1, self::maxQuantityFor($item->product))));
                    $existing->save();
                }

                $guest->delete();
            });
        }

        Cookie::queue(Cookie::forget(self::COOKIE));
        $this->resolved = false;
    }

    private function findCart(): ?Cart
    {
        $user = $this->auth->guard()->user();

        if ($user !== null) {
            return Cart::query()->where('user_id', $user->getAuthIdentifier())->first();
        }

        $token = $this->request->cookie(self::COOKIE);

        if (! is_string($token) || $token === '') {
            return null;
        }

        return Cart::query()->whereNull('user_id')->where('token', $token)->first();
    }

    private function cartOrCreate(): Cart
    {
        if ($cart = $this->cart()) {
            return $cart;
        }

        $user = $this->auth->guard()->user();

        if ($user !== null) {
            $this->cart = Cart::query()->firstOrCreate(['user_id' => $user->getAuthIdentifier()]);
        } else {
            $this->cart = Cart::query()->create(['token' => Str::random(40)]);
            Cookie::queue(self::COOKIE, $this->cart->token, self::COOKIE_MINUTES);
        }

        return $this->cart;
    }

    private function findItem(int $productId): ?CartItem
    {
        return $this->cart()?->items()->where('product_id', $productId)->with('product')->first();
    }
}
