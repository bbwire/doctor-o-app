<?php

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    protected $model = Prescription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'consultation_id' => Consultation::factory(),
            'doctor_id' => User::factory()->doctor(),
            'patient_id' => User::factory()->patient(),
            'medications' => [
                [
                    'name' => fake()->word(),
                    'dosage' => '1 tablet',
                    'frequency' => 'Twice daily',
                    'duration' => '5 days',
                ],
            ],
            'instructions' => fake()->sentence(),
            'issued_at' => now(),
            'status' => fake()->randomElement(['active', 'completed', 'cancelled']),
        ];
    }
}
