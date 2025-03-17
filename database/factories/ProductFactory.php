<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 5, 500),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the product is in stock.
     *
     * @return Factory
     */
    public function inStock()
    {
        return $this->state(function (array $attributes) {
            return [
                'stock_quantity' => $this->faker->numberBetween(10, 100),
            ];
        });
    }

    /**
     * Indicate that the product is out of stock.
     *
     * @return Factory
     */
    public function outOfStock()
    {
        return $this->state(function (array $attributes) {
            return [
                'stock_quantity' => 0,
            ];
        });
    }

    /**
     * Indicate that the product has low stock.
     *
     * @return Factory
     */
    public function lowStock()
    {
        return $this->state(function (array $attributes) {
            return [
                'stock_quantity' => $this->faker->numberBetween(1, 5),
            ];
        });
    }
}
