<?php

namespace App\Livewire;

use App\Enums\DeliveryMethod;
use App\Services\Cart\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Корзина')]
class CartPage extends Component
{
    public function setQuantity(CartService $cart, int $productId, int $quantity): void
    {
        $cart->setQuantity($productId, $quantity);
        $this->dispatch('cart-updated');
    }

    public function remove(CartService $cart, int $productId): void
    {
        $cart->remove($productId);
        $this->dispatch('cart-updated');
    }

    public function clear(CartService $cart): void
    {
        $cart->clear();
        $this->dispatch('cart-updated');
    }

    public function render(CartService $cart): View
    {
        $summary = $cart->summary();

        return view('livewire.cart-page', [
            'summary' => $summary,
            'courierPrice' => $summary->deliveryPrice(DeliveryMethod::Courier),
            'freeFrom' => (int) config('shop.delivery.free_from'),
        ]);
    }
}
