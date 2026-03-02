<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->words(rand(2, 4), true),
            'description' => fake()->optional()->sentence(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-??')),
            'barcode' => fake()->optional(0.7)->ean13(),
            'type' => 'product',
            'track_inventory' => true,
            'min_stock' => fake()->numberBetween(5, 20),
            'max_stock' => fake()->optional()->numberBetween(100, 500),
            'cost' => fake()->randomFloat(2, 10, 500),
            'image_url' => null,
            'is_active' => true,
        ];
    }

    /**
     * Set the product type to service.
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'service',
            'track_inventory' => false,
        ]);
    }

    /**
     * Set the product type to variant.
     */
    public function variant(Product $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'variant',
            'parent_id' => $parent->id,
            'category_id' => $parent->category_id,
        ]);
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set inventory tracking off.
     */
    public function noInventoryTracking(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_inventory' => false,
        ]);
    }

    /**
     * Assign the product to a specific category.
     */
    public function forCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
        ]);
    }

    /**
     * Set a specific cost.
     */
    public function withCost(float $cost): static
    {
        return $this->state(fn (array $attributes) => [
            'cost' => $cost,
        ]);
    }
}