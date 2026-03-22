<?php

namespace Database\Factories;

use App\Models\CondicionAtmosferica;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ruta>
 */
class RutaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_user' => User::factory(), // crea usuario automáticamente

            'path' => $this->faker->unique()->filePath(),

            'descripcion' => $this->faker->paragraph(),

            'provincia_inicio' => $this->faker->randomElement([
                'León', 'Madrid', 'Barcelona', 'Valencia', 'Sevilla'
            ]),

            'provincia_fin' => $this->faker->randomElement([
                'León', 'Madrid', 'Barcelona', 'Valencia', 'Sevilla'
            ]),

            'temperatura' => $this->faker->numberBetween(-5, 40),

            'id_condicion_atmosferica' => CondicionAtmosferica::factory(),

            'puntuacion' => $this->faker->numberBetween(1, 5),
        ];
    }
}
