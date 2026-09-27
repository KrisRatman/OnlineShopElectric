<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Storage;

it('наполняет демо-магазин и при повторном запуске ничего не дублирует', function () {
    Storage::fake('public');

    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(User::where('is_admin', true)->count())->toBe(1)
        ->and(Category::whereNull('parent_id')->pluck('name')->all())->toBe(['Компьютеры', 'Ноутбуки', 'Смартфоны'])
        ->and(Product::count())->toBe(36)
        ->and(Order::count())->toBe(72);

    // Суммы заказов сходятся с позициями.
    Order::with('items')->get()->each(function (Order $order) {
        expect($order->subtotal)->toBe($order->items->sum('total'))
            ->and($order->total)->toBe($order->subtotal + $order->delivery_price);
    });

    $product = Product::with('mainImage')->first();
    Storage::disk('public')->assertExists($product->mainImage->path);
});
