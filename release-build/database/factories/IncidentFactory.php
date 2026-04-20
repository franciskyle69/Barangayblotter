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
            'incident_type' => $this->faker->randomElement(['Theft', 'Assault', 'Dispute', 'Noise Complaint']),
            'description' => $this->faker->paragraph(),
            'location' => $this->faker->streetAddress(),
            'complainant_name' => $this->faker->name(),
            'complainant_contact' => $this->faker->phoneNumber(),
            'complainant_address' => $this->faker->address(),
            'respondent_name' => $this->faker->name(),
            'respondent_contact' => $this->faker->phoneNumber(),
            'respondent_address' => $this->faker->address(),
            'status' => \App\Models\Incident::STATUS_OPEN,
            'incident_date' => $this->faker->dateTimeThisMonth(),
            'submitted_online' => false,
        ];
    }
}
