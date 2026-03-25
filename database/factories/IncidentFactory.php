<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Incident>
 */
class IncidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => null, // Will be set by middleware/factory
            'reported_by_user_id' => null,
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'location' => $this->faker->streetAddress(),
            'status' => \App\Models\Incident::STATUS_OPEN,
            'incident_date' => $this->faker->dateTimeThisMonth(),
        ];
    }
}
