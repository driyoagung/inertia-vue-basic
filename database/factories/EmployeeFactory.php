<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'position' => fake()->jobTitle(),
            'department' => fake()->randomElement(['Engineering', 'Sales', 'Marketing', 'HR', 'Finance']),
            'salary' => fake()->numberBetween(40000, 150000),
            'status' => fake()->randomElement(['active', 'active', 'active', 'inactive', 'on-leave']),
        ];
    }
}
