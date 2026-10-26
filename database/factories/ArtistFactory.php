<?php

namespace Database\Factories;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Artist>
 */
class ArtistFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->company(),
            'country' => fake()->country(),
            'genre' => fake()->randomElement(config('discoteca.genres')),
            'bio' => null,
            'photo_path' => null,
        ];
    }
}
