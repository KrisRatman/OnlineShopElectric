<?php

use App\Livewire\Catalog;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use Livewire\Livewire;

beforeEach(function () {
    $this->laptops = Category::factory()->create(['name' => 'Ноутбуки', 'slug' => 'noutbuki']);
    $this->gaming = Category::factory()->childOf($this->laptops)->create(['name' => 'Игровые', 'slug' => 'igrovye']);
    $this->phones = Category::factory()->create(['name' => 'Смартфоны', 'slug' => 'smartfony']);

    $this->asus = Brand::factory()->create(['name' => 'ASUS', 'slug' => 'asus']);
    $this->apple = Brand::factory()->create(['name' => 'Apple', 'slug' => 'apple']);
    $this->ram = ProductAttribute::create(['name' => 'Оперативная память', 'slug' => 'ram', 'is_filterable' => true]);

    $make = function (Category $category, Brand $brand, string $name, int $rubles, string $ram, int $stock = 5) {
        $product = Product::factory()->for($category)->for($brand)->price($rubles)->stock($stock)->create(['name' => $name]);
        AttributeValue::create(['product_id' => $product->id, 'attribute_id' => $this->ram->id, 'value' => $ram]);

        return $product;
    };

    $this->tuf = $make($this->gaming, $this->asus, 'ASUS TUF Gaming A15', 104990, '16 ГБ');
    $this->rog = $make($this->gaming, $this->asus, 'ASUS ROG Zephyrus', 219990, '32 ГБ', stock: 0);
    $this->mac = $make($this->laptops, $this->apple, 'MacBook Air M3', 119990, '8 ГБ');
    $this->iphone = $make($this->phones, $this->apple, 'iPhone 15', 79990, '6 ГБ');
});

function names($component): array
{
    return $component->instance()->products->pluck('name')->all();
}

it('родительская категория показывает товары подкатегорий', function () {
    $component = Livewire::test(Catalog::class, ['category' => $this->laptops]);

    expect(names($component))->toEqualCanonicalizing(['ASUS TUF Gaming A15', 'ASUS ROG Zephyrus', 'MacBook Air M3']);
});

it('ищет по названию и бренду', function () {
    expect(names(Livewire::test(Catalog::class)->set('q', 'zephyrus')))->toBe(['ASUS ROG Zephyrus'])
        ->and(names(Livewire::test(Catalog::class)->set('q', 'apple')))->toEqualCanonicalizing(['MacBook Air M3', 'iPhone 15']);
});

it('фильтрует по бренду, цене, наличию и характеристикам', function () {
    $filter = fn (array $state) => names(Livewire::test(Catalog::class, ['category' => $this->laptops])->set($state));

    expect($filter(['brands' => ['apple']]))->toBe(['MacBook Air M3'])
        ->and($filter(['priceFrom' => 110000, 'priceTo' => 200000]))->toBe(['MacBook Air M3'])
        ->and($filter(['inStock' => true]))->not->toContain('ASUS ROG Zephyrus')
        ->and($filter(['attrs' => ['ram' => ['16 ГБ', '32 ГБ']]]))->toEqualCanonicalizing(['ASUS TUF Gaming A15', 'ASUS ROG Zephyrus']);
});

it('сортирует по цене, а товары без наличия всегда в конце', function () {
    $asc = names(Livewire::test(Catalog::class)->set('sort', 'price_asc'));
    $desc = names(Livewire::test(Catalog::class)->set('sort', 'price_desc'));

    expect($asc)->toBe(['iPhone 15', 'ASUS TUF Gaming A15', 'MacBook Air M3', 'ASUS ROG Zephyrus'])
        ->and($desc)->toBe(['MacBook Air M3', 'ASUS TUF Gaming A15', 'iPhone 15', 'ASUS ROG Zephyrus']);
});

it('в фильтрах только бренды и значения, которые есть в категории', function () {
    $component = Livewire::test(Catalog::class, ['category' => $this->gaming]);

    expect($component->instance()->brandOptions->pluck('slug')->all())->toBe(['asus'])
        ->and($component->instance()->attributeOptions->first()['values'])->toBe(['16 ГБ', '32 ГБ']);
});

it('скрытые товары и категории на витрину не попадают', function () {
    $this->mac->update(['is_active' => false]);
    $this->phones->update(['is_active' => false]);

    expect(names(Livewire::test(Catalog::class)))->not->toContain('MacBook Air M3');
    $this->get(route('catalog.category', $this->phones))->assertNotFound();
    $this->get(route('products.show', $this->mac))->assertNotFound();
});

it('открывает главную, каталог и карточку товара', function () {
    $this->get('/')->assertOk()->assertSee('Ноутбуки');
    $this->get('/catalog?q=iphone')->assertOk()->assertSee('iPhone 15');
    $this->get(route('catalog.category', $this->gaming))->assertOk()->assertSee('ASUS TUF Gaming A15');
    $this->get(route('products.show', $this->tuf))
        ->assertOk()
        ->assertSee('104 990 ₽')
        ->assertSee('Оперативная память')
        ->assertSee('16 ГБ');
});
