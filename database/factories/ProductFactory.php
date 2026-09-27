<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Ноутбук '.Str::ucfirst(fake()->unique()->words(2, true));

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'sku' => 'SKU-'.fake()->unique()->numberBetween(100000, 999999),
            'description' => fake()->paragraph(),
            'price' => 5000000,
            'stock' => 10,
            'is_active' => true,
            'is_featured' => false,
        ];
    }

    /** Цена в рублях — так читабельнее в тестах. */
    public function price(int $rubles, ?int $oldRubles = null): static
    {
        return $this->state(fn () => ['price' => $rubles * 100, 'old_price' => $oldRubles ? $oldRubles * 100 : null]);
    }

    public function stock(int $stock): static
    {
        return $this->state(fn () => ['stock' => $stock]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
