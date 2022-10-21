<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'sku' => $this->faker->unique()->bothify('SKU-####'),
            'marketplace_id' => $this->faker->randomElement(['A1PA6795UKMFR9', 'ATVPDKIKX0DER', 'A1RKKUPIHCS9HS']),
            'product_type' => null,
            'price' => $this->faker->randomFloat(2, 1, 1000),
            'status' => 0,
        ];
    }
}
