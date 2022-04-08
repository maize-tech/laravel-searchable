<?php

namespace Maize\Searchable\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Maize\Searchable\Tests\Models\Team;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition()
    {
        return [
            'id' => $this->faker->uuid(),
            'name' => 'maize-tech',
        ];
    }
}
