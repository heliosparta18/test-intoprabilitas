<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockMutation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMutation>
 */
class StockMutationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => fake()->randomElement(['in', 'out']),
            'quantity' => fake()->numberBetween(1, 10),
            'notes' => fake()->sentence(),
        ];
    }
}
