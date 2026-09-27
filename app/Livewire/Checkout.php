<?php

namespace App\Livewire;

use App\Actions\CheckoutData;
use App\Actions\PlaceOrder;
use App\Enums\DeliveryMethod;
use App\Services\Cart\CartException;
use App\Services\Cart\CartService;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentService;
use App\Support\Phone;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Оформление заказа')]
class Checkout extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $delivery = 'courier';

    public string $address = '';

    public string $comment = '';

    public bool $agree = false;

    public function mount(CartService $cart): void
    {
        if (! $cart->summary()->canCheckout()) {
            $this->redirectRoute('cart');

            return;
        }

        if ($user = auth()->user()) {
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = Phone::format($user->phone);
        }
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail) {
                if (Phone::normalize((string) $value) === null) {
                    $fail('Укажите телефон в формате +7 900 000-00-00.');
                }
            }],
            'delivery' => ['required', Rule::enum(DeliveryMethod::class)],
            'address' => [Rule::requiredIf(fn () => $this->delivery === DeliveryMethod::Courier->value), 'nullable', 'string', 'max:500'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'agree' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'address.required' => 'Укажите адрес доставки.',
            'agree.accepted' => 'Нужно согласие на обработку персональных данных.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'name' => 'имя',
            'email' => 'email',
            'phone' => 'телефон',
            'address' => 'адрес',
            'comment' => 'комментарий',
        ];
    }

    public function placeOrder(CartService $cart, PlaceOrder $placeOrder, PaymentService $payments): void
    {
        $this->validate();

        $key = 'checkout:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('form', 'Слишком много заказов подряд. Попробуйте через несколько минут.');

            return;
        }

        RateLimiter::hit($key, 600);

        $cartModel = $cart->cart();

        if ($cartModel === null) {
            $this->redirectRoute('cart');

            return;
        }

        try {
            $order = $placeOrder->handle($cartModel, new CheckoutData(
                name: trim($this->name),
                email: mb_strtolower(trim($this->email)),
                phone: Phone::normalize($this->phone),
                delivery: DeliveryMethod::from($this->delivery),
                address: trim($this->address) ?: null,
                comment: trim($this->comment) ?: null,
            ), auth()->user());
        } catch (CartException $e) {
            $this->addError('form', $e->getMessage());

            return;
        }

        $this->dispatch('cart-updated');

        // Заказ создан и товар зарезервирован. Если ЮKassa сейчас недоступна —
        // покупатель попадёт на страницу заказа и оплатит оттуда.
        try {
            $payment = $payments->start($order);
            $this->redirect($payment->confirmation_url);
        } catch (PaymentException $e) {
            session()->flash('error', $e->getMessage());
            $this->redirectRoute('orders.show', $order->token);
        }
    }

    public function render(CartService $cart): View
    {
        $summary = $cart->summary();
        $method = DeliveryMethod::tryFrom($this->delivery) ?? DeliveryMethod::Courier;

        return view('livewire.checkout', [
            'summary' => $summary,
            'method' => $method,
            'deliveryPrice' => $summary->deliveryPrice($method),
            'total' => $summary->total($method),
            'courierPrice' => $summary->deliveryPrice(DeliveryMethod::Courier),
        ]);
    }
}
