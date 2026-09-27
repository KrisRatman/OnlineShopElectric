<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DemoPaymentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentReturnController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\YooKassaWebhookController;
use App\Http\Middleware\VerifyYooKassaIp;
use App\Livewire\CartPage;
use App\Livewire\Catalog;
use App\Livewire\Checkout;
use App\Livewire\OrderPage;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Каталог, поиск и фильтры
Route::livewire('/catalog', Catalog::class)->name('catalog');
Route::livewire('/catalog/{category:slug}', Catalog::class)->name('catalog.category');
Route::get('/product/{product:slug}', [ProductController::class, 'show'])->name('products.show');

// Корзина и оформление
Route::livewire('/cart', CartPage::class)->name('cart');
Route::livewire('/checkout', Checkout::class)->name('checkout');

// Заказ открывается по секретной ссылке: так его видит и гость, оформивший заказ без регистрации.
Route::livewire('/orders/{order:token}', OrderPage::class)->name('orders.show');
Route::get('/orders/{order:token}/payment-return', PaymentReturnController::class)->name('orders.payment-return');

// Уведомления ЮKassa
Route::post('/payments/yookassa/webhook', YooKassaWebhookController::class)
    ->middleware(VerifyYooKassaIp::class)
    ->name('payments.yookassa.webhook');

// Демо-оплата вместо формы ЮKassa (YOOKASSA_DEMO=true)
Route::get('/demo-payment/{payment}', [DemoPaymentController::class, 'show'])->name('demo-payment.show');
Route::post('/demo-payment/{payment}', [DemoPaymentController::class, 'complete'])->name('demo-payment.complete');

// Покупатель
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/account', [AccountController::class, 'index'])->name('account');
    Route::put('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile');
});
