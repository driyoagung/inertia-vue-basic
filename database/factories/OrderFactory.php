<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_number' => 'INV-'.$this->faker->numerify('########').'-'.$this->faker->randomNumber(5, true),
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->safeEmail(),
            'status' => $this->faker->randomElement(['pending', 'paid', 'shipped', 'completed', 'cancelled']),
            'total' => $this->faker->randomFloat(2, 25, 500000),
            'ordered_at' => $this->faker->dateTimeBetween('-2 years'),
        ];
    }
}
