<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Secondaryimage>
 */
class SecondaryimageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'img' => 'https://picsum.photos/400/500?random=' . rand(1, 1000),
            'plan_id' => $this->faker->numberBetween(1, 50)
        ];
    }
}
