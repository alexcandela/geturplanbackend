<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $names = [
        'Cueva sin explorar',
        'Rio perfecto para saltos',
        'Playa con camping',
        'Excursión a la montaña',
        'Ruta en bicicleta por el bosque',
        'Visita a cascadas escondidas',
        'Tour gastronómico por la ciudad',
        'Exploración de ruinas antiguas',
        'Camping bajo las estrellas',
        'Aventura en el desierto',
        'Paseo en velero por el lago',
        'Senderismo en el parque nacional',
        'Viaje en globo aerostático',
        'Curso de surf en la playa',
        'Ruta en kayak por el río',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement($this->names),
            'description' => $this->faker->paragraph(3),
            'city' => $this->faker->city(),
            'province' => $this->faker->state(),
            'img' => 'https://picsum.photos/400/500?random=' . rand(1, 1000),
            'latitude' => $this->faker->latitude(36.0, 43.7),
            'longitude' => $this->faker->longitude(-9.5, 3.7),
            'user_id' => User::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
