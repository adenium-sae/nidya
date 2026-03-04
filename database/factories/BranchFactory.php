<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Branch',
            'code' => strtoupper(fake()->bothify('BR-###')),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'allow_sales' => true,
            'allow_inventory' => true,
            'is_active' => true,
        ];
    }
}
