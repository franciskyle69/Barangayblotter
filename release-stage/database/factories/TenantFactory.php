<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_id' => 1, // Assumes a default plan exists
            'name' => $this->faker->company(),
            'slug' => $this->faker->slug(),
            'subdomain' => $this->faker->slug(),
            'custom_domain' => null,
            'barangay' => $this->faker->city(),
            'address' => $this->faker->address(),
            'contact_phone' => $this->faker->phoneNumber(),
            'is_active' => true,
        ];
    }
}
