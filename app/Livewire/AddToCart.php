<?php

namespace App\Livewire;

use App\Models\Product;
use App\Services\Cart\CartException;
use App\Services\Cart\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Кнопка «В корзину» в карточке товара и на странице товара (с выбором количества). */
class AddToCart extends Component
{
    #[Locked]
    public int $productId;

    #[Locked]
    public bool $withQuantity = false;

    #[Locked]
    public int $max = 0;

    public int $quantity = 1;

    public int $inCart = 0;

    public function mount(Product $product, CartService $cart, bool $withQuantity = false): void
    {
        $this->productId = $product->id;
        $this->withQuantity = $withQuantity;
        $this->max = CartService::maxQuantityFor($product);
        $this->inCart = $cart->quantityOf($product);
    }

    public function increment(): void
    {
        $this->quantity = min($this->quantity + 1, max(1, $this->max - $this->inCart));
    }

    public function decrement(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function add(CartService $cart): void
    {
        $product = Product::query()->findOrFail($this->productId);

        try {
            $this->inCart = $cart->add($product, max(1, $this->quantity));
            $this->quantity = 1;
            $this->dispatch('cart-updated');
            $this->dispatch('toast', message: "«{$product->name}» в корзине", type: 'success');
        } catch (CartException $e) {
            $this->max = CartService::maxQuantityFor($product);
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function render(): View
    {
        return view('livewire.add-to-cart');
    }
}
