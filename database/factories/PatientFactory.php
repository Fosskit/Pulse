<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'PAT-' . fake()->unique()->numberBetween(10000, 99999),
            'surname' => fake()->lastName(),
            'name' => fake()->firstName(),
            'telephone' => fake()->phoneNumber(),
            'sex' => fake()->randomElement(['M', 'F']),
            'birthdate' => fake()->date(),
            'multiple_birth' => false,
            'nationality_id' => 1, // Default nationality
            'marital_status_id' => 1, // Default marital status
            'occupation_id' => 1, // Default occupation
            'deceased' => false,
            'deceased_at' => null,
            'status_id' => 1, // Default to Active status
        ];
    }

    /**
     * Indicate that the patient is deceased.
     */
    public function deceased(): static
    {
        return $this->state(fn (array $attributes) => [
            'deceased' => true,
            'deceased_at' => fake()->dateTimeBetween('-5 years', 'now'),
        ]);
    }

    /**
     * Indicate that the patient is from a multiple birth.
     */
    public function multipleBirth(): static
    {
        return $this->state(fn (array $attributes) => [
            'multiple_birth' => true,
        ]);
    }
}
