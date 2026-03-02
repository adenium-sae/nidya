<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'branch_id' => null,
            'address_id' => null,
            'name' => fake()->words(2, true) . ' Warehouse',
            'code' => strtoupper(fake()->unique()->bothify('WH-###')),
            'type' => fake()->randomElement(['central', 'branch', 'transit', 'virtual']),
            'is_active' => true,
        ];
    }

    /**
     * Set the warehouse type to central.
     */
    public function central(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'central',
        ]);
    }

    /**
     * Set the warehouse type to branch.
     */
    public function branch(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'branch',
        ]);
    }

    /**
     * Set the warehouse type to transit.
     */
    public function transit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'transit',
        ]);
    }

    /**
     * Indicate that the warehouse is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Assign the warehouse to a specific store.
     */
    public function forStore(Store $store): static
    {
        return $this->state(fn (array $attributes) => [
            'store_id' => $store->id,
        ]);
    }
}