<?php

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consultation>
 */
class ConsultationFactory extends Factory
{
    protected $model = Consultation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => User::factory()->patient(),
            'doctor_id' => User::factory()->doctor(),
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+30 days'),
            'consultation_type' => fake()->randomElement(['text', 'audio', 'video']),
            'status' => fake()->randomElement(['scheduled', 'completed', 'cancelled']),
            'reason' => fake()->sentence(),
            'notes' => fake()->paragraph(),
            'metadata' => [
                'source' => 'web',
            ],
        ];
    }
}
