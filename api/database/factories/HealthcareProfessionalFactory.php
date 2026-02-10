<?php

namespace Database\Factories;

use App\Models\HealthcareProfessional;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthcareProfessional>
 */
class HealthcareProfessionalFactory extends Factory
{
    protected $model = HealthcareProfessional::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->doctor(),
            'institution_id' => Institution::factory(),
            'speciality' => fake()->randomElement(['Cardiology', 'Pediatrics', 'Dermatology']),
            'license_number' => fake()->bothify('LIC-#####'),
            'bio' => fake()->paragraph(),
            'qualifications' => [fake()->sentence(3), fake()->sentence(4)],
            'is_active' => true,
        ];
    }
}
