<?php

use App\Enums\OrderStatus;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\ShopStats;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->actingAs(User::factory()->admin()->create());
});

it('открывает все разделы админки', function () {
    $product = Product::factory()->create();
    $order = Order::factory()->withItem($product)->create();

    foreach (['/admin', '/admin/orders', "/admin/orders/{$order->id}", '/admin/products', '/admin/products/create',
        "/admin/products/{$product->slug}/edit", '/admin/categories', '/admin/brands', '/admin/product-attributes'] as $url) {
        $this->get($url)->assertOk();
    }
});

it('виджеты дашборда считают выручку по оплаченным заказам', function () {
    Order::factory()->status(OrderStatus::Paid)->create(['total' => 150000]);
    Order::factory()->status(OrderStatus::Completed)->create(['total' => 250000]);
    Order::factory()->create(['total' => 999900]);

    Livewire::test(ShopStats::class)->assertSee('4 000 ₽');
    Livewire::test(RevenueChart::class)->assertOk();
    Livewire::test(LatestOrders::class)->assertOk();
});

it('создаёт товар: цена в рублях сохраняется в копейках', function () {
    $category = Category::factory()->create();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Смартфон Тест',
            'slug' => 'smartfon-test',
            'sku' => 'TEST-1',
            'category_id' => $category->id,
            'price' => '1490.50',
            'stock' => 7,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Product::sole())
        ->price->toBe(149050)
        ->stock->toBe(7);
});

it('старая цена должна быть больше текущей', function () {
    $product = Product::factory()->price(1000)->create();

    Livewire::test(EditProduct::class, ['record' => $product->slug])
        ->fillForm(['old_price' => '900'])
        ->call('save')
        ->assertHasFormErrors(['old_price']);
});

it('меняет остаток прямо из списка товаров', function () {
    $product = Product::factory()->stock(2)->create();

    Livewire::test(ListProducts::class)
        ->callAction(TestAction::make('stock')->table($product), ['stock' => 25])
        ->assertHasNoFormErrors();

    expect($product->fresh()->stock)->toBe(25);
});

it('ведёт оплаченный заказ по статусам и пишет покупателю', function () {
    $order = Order::factory()->status(OrderStatus::Paid)->create();

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertActionHidden('completed')
        ->callAction('processing')
        ->callAction('shipped')
        ->callAction('completed');

    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
    Notification::assertSentToTimes($order, OrderStatusChanged::class, 3);
});

it('отмена заказа из админки возвращает товар на склад', function () {
    $product = Product::factory()->stock(0)->create();
    $order = Order::factory()->withItem($product, 2)->status(OrderStatus::Paid)->create();

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->callAction('cancel', ['reason' => 'Покупатель передумал']);

    expect($order->fresh())
        ->status->toBe(OrderStatus::Cancelled)
        ->cancel_reason->toBe('Покупатель передумал');
    expect($product->fresh()->stock)->toBe(2);
});

it('у неоплаченного заказа нельзя поставить «Собирается»', function () {
    $order = Order::factory()->create();

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->assertActionHidden('processing')
        ->assertActionVisible('cancel');
});

it('вкладки заказов и категории работают', function () {
    Order::factory()->status(OrderStatus::Paid)->create();

    Livewire::test(ListOrders::class)->set('activeTab', 'paid')->assertCountTableRecords(1);
    Livewire::test(ListCategories::class)->assertOk();
});
