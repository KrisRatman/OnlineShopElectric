<?php

namespace App\Livewire;

use App\Services\Cart\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/** Значок корзины в шапке: обновляется по событию cart-updated. */
class CartCounter extends Component
{
    #[On('cart-updated')]
    public function refreshCount(): void
    {
        // Перерисовка сама пересчитает количество.
    }

    public function render(CartService $cart): View
    {
        return view('livewire.cart-counter', ['count' => $cart->count()]);
    }
}
