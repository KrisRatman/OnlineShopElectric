<?php

namespace Database\Seeders;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Support\DemoCatalog;
use Database\Seeders\Support\DemoImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Демо-магазин «Вольт»: каталог электроники, покупатели и история заказов за 30 дней.
 *
 * Случайность с фиксированным зерном — при каждом запуске одни и те же данные,
 * скриншоты стабильны. Повторный запуск ничего не дублирует.
 */
class DemoSeeder extends Seeder
{
    private Randomizer $random;

    public function run(): void
    {
        if (User::query()->where('email', config('shop.demo.email'))->exists()) {
            $this->command?->info('Демо-данные уже есть — пропускаю.');

            return;
        }

        $this->random = new Randomizer(new Mt19937(2026));

        $this->users();
        $categories = $this->categories();
        $attributes = $this->attributes();
        $products = $this->products($categories, $attributes);
        $this->orders($products);
    }

    private function users(): void
    {
        User::query()->forceCreate([
            'name' => 'Администратор',
            'email' => config('shop.demo.email'),
            'password' => config('shop.demo.password'),
            'is_admin' => true,
        ]);

        User::query()->forceCreate([
            'name' => 'Анна Смирнова',
            'email' => 'buyer@example.com',
            'phone' => '+79161234567',
            'password' => 'password',
        ]);
    }

    /** @return array<string, Category> */
    private function categories(): array
    {
        $bySlug = [];
        $sort = 0;

        foreach (DemoCatalog::categories() as $slug => $root) {
            $parent = Category::query()->create(['name' => $root['name'], 'slug' => $slug, 'sort' => $sort++]);
            $bySlug[$slug] = $parent;

            foreach (array_keys($root['children']) as $i => $childSlug) {
                $bySlug[$childSlug] = Category::query()->create([
                    'parent_id' => $parent->id,
                    'name' => $root['children'][$childSlug],
                    'slug' => $childSlug,
                    'sort' => $i,
                ]);
            }
        }

        return $bySlug;
    }

    /** @return array<string, ProductAttribute> */
    private function attributes(): array
    {
        $bySlug = [];
        $sort = 0;

        foreach (DemoCatalog::attributes() as $slug => [$name, $filterable]) {
            $bySlug[$slug] = ProductAttribute::query()->create([
                'name' => $name,
                'slug' => $slug,
                'is_filterable' => $filterable,
                'sort' => $sort++,
            ]);
        }

        return $bySlug;
    }

    /**
     * @param  array<string, Category>  $categories
     * @param  array<string, ProductAttribute>  $attributes
     * @return list<Product>
     */
    private function products(array $categories, array $attributes): array
    {
        $disk = Storage::disk('public');
        $brands = [];
        $products = [];

        foreach (DemoCatalog::products() as $item) {
            $brands[$item['brand']] ??= Brand::query()->firstOrCreate(
                ['slug' => Str::slug($item['brand'])],
                ['name' => $item['brand']],
            );

            $slug = Str::slug($item['name']);

            // id по порядку каталога: «Новинки» — последние товары списка.
            $product = Product::query()->create([
                'category_id' => $categories[$item['category']]->id,
                'brand_id' => $brands[$item['brand']]->id,
                'name' => $item['name'],
                'slug' => $slug,
                'sku' => $item['sku'],
                'description' => DemoCatalog::description($item),
                'price' => $item['price'] * 100,
                'old_price' => $item['old'] ? $item['old'] * 100 : null,
                'stock' => $item['stock'],
                'is_featured' => $item['featured'],
            ]);

            $path = "products/demo/{$slug}.svg";
            $disk->put($path, DemoImages::make($item['image'], $item['accent'], $item['body']));
            $product->images()->create(['path' => $path, 'sort' => 0]);

            foreach ($item['specs'] as $key => $value) {
                AttributeValue::query()->create([
                    'product_id' => $product->id,
                    'attribute_id' => $attributes[$key]->id,
                    'value' => $value,
                ]);
            }

            $products[] = $product;
        }

        return $products;
    }

    /**
     * История заказов за 30 дней: выручка на графике, все статусы в админке.
     * Остатки не трогаем — это прошлые продажи.
     *
     * @param  list<Product>  $products
     */
    private function orders(array $products): void
    {
        $names = [
            'Иван Петров', 'Мария Козлова', 'Дмитрий Соколов', 'Елена Новикова', 'Алексей Морозов',
            'Ольга Волкова', 'Сергей Лебедев', 'Наталья Павлова', 'Андрей Фёдоров', 'Татьяна Михайлова',
            'Павел Орлов', 'Юлия Никитина', 'Максим Захаров', 'Ксения Белова', 'Никита Егоров',
        ];
        $streets = ['ул. Тверская', 'Ленинский пр-т', 'ул. Арбат', 'Кутузовский пр-т', 'ул. Профсоюзная', 'ул. Маросейка'];
        $buyer = User::query()->where('email', 'buyer@example.com')->first();
        $now = CarbonImmutable::now();

        for ($n = 0; $n < 72; $n++) {
            $createdAt = $now->subMinutes($this->random->getInt(20, 60 * 24 * 30));
            $age = $now->diffInHours($createdAt, true);
            $status = $this->statusFor($age);
            $forBuyer = $n % 20 === 0;
            $name = $forBuyer ? $buyer->name : $names[$this->random->getInt(0, count($names) - 1)];

            $lines = [];

            foreach ($this->random->pickArrayKeys($products, $this->random->getInt(1, 3)) as $key) {
                $product = $products[$key];
                // Недорогое берут и по две штуки — например, телефоны себе и родителям.
                $quantity = $product->price < 4000000 && $this->random->getInt(0, 4) === 0 ? 2 : 1;
                $lines[] = [$product, $quantity];
            }

            $subtotal = array_sum(array_map(fn ($l) => $l[0]->price * $l[1], $lines));
            $delivery = $this->random->getInt(0, 2) === 0 ? DeliveryMethod::Pickup : DeliveryMethod::Courier;
            $deliveryPrice = $delivery->price($subtotal);
            $paidAt = $status->isPaid() ? $createdAt->addMinutes($this->random->getInt(2, 15)) : null;

            $order = Order::query()->forceCreate([
                'user_id' => $forBuyer ? $buyer->id : null,
                'token' => Str::random(40),
                'status' => $status,
                'customer_name' => $name,
                'customer_email' => $forBuyer ? $buyer->email : Str::slug(Str::before($name, ' ')).$n.'@example.com',
                'customer_phone' => '+79'.str_pad((string) $this->random->getInt(0, 999999999), 9, '0', STR_PAD_LEFT),
                'delivery_method' => $delivery,
                'delivery_address' => $delivery === DeliveryMethod::Courier
                    ? 'Москва, '.$streets[$this->random->getInt(0, count($streets) - 1)].', д. '.$this->random->getInt(1, 60).', кв. '.$this->random->getInt(1, 200)
                    : null,
                'subtotal' => $subtotal,
                'delivery_price' => $deliveryPrice,
                'total' => $subtotal + $deliveryPrice,
                'payment_due_at' => $createdAt->addMinutes((int) config('shop.payment_ttl')),
                'paid_at' => $paidAt,
                'cancelled_at' => $status === OrderStatus::Cancelled ? $createdAt->addHour() : null,
                'cancel_reason' => $status === OrderStatus::Cancelled ? 'Истёк срок оплаты' : null,
                'created_at' => $createdAt,
                'updated_at' => $paidAt ?? $createdAt,
            ]);

            foreach ($lines as [$product, $quantity]) {
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'price' => $product->price,
                    'quantity' => $quantity,
                    'total' => $product->price * $quantity,
                ]);
            }

            if ($status !== OrderStatus::PendingPayment) {
                $order->payments()->forceCreate([
                    'provider' => 'yookassa',
                    'provider_payment_id' => sprintf('2e%06x-000f-5000-9000-%012x', $n + 1, $this->random->getInt(0, PHP_INT_MAX) % 0xFFFFFFFFFFFF),
                    'idempotence_key' => (string) Str::uuid(),
                    'status' => $paidAt ? PaymentStatus::Succeeded : PaymentStatus::Canceled,
                    'amount' => $order->total,
                    'cancellation_reason' => $paidAt ? null : 'expired_on_confirmation',
                    'paid_at' => $paidAt,
                    'created_at' => $createdAt,
                    'updated_at' => $paidAt ?? $createdAt->addHour(),
                ]);
            }
        }
    }

    /** Старые заказы уже выполнены, свежие — ещё в работе. */
    private function statusFor(float $ageHours): OrderStatus
    {
        $roll = $this->random->getInt(1, 100);

        if ($ageHours < 1) {
            return $roll <= 60 ? OrderStatus::PendingPayment : OrderStatus::Paid;
        }

        if ($roll <= 12) {
            return OrderStatus::Cancelled;
        }

        return match (true) {
            $ageHours < 36 => $roll <= 70 ? OrderStatus::Paid : OrderStatus::Processing,
            $ageHours < 72 => $roll <= 50 ? OrderStatus::Shipped : OrderStatus::Processing,
            default => $roll <= 90 ? OrderStatus::Completed : OrderStatus::Shipped,
        };
    }
}
