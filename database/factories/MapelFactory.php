<?php

namespace Database\Factories;

use App\Models\Mapel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mapel>
 */
class MapelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->bothify('???-###'),
            'nama' => fake()->unique()->words(2, true),
        ];
    }
}