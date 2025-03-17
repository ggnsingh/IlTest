<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'description' => $this->faker->sentence(),
            'total_amount' => $this->faker->randomFloat(2, 10, 1000),
            'order_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'cancelled']),
        ];
    }

    /**
     * Indicate that the order is pending.
     *
     * @return Factory
     */
    public function pending(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
            ];
        });
    }

    /**
     * Indicate that the order is processing.
     *
     * @return Factory
     */
    public function processing(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'processing',
            ];
        });
    }

    /**
     * Indicate that the order is completed.
     *
     * @return Factory
     */
    public function completed(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
            ];
        });
    }

    /**
     * Indicate that the order is cancelled.
     *
     * @return Factory
     */
    public function cancelled(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
            ];
        });
    }
}
