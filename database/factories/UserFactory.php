<?php

namespace Maize\Searchable\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Maize\Searchable\Tests\Models\User;
use Maize\Searchable\Tests\Models\Team;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'first_name' => 'Name',
            'last_name' => 'Surname',
            'email' => 'name.surname@example.com',
            'description' => '{ "en": "Just a random guy" }',
            'team_id' => Team::factory()->create(),
        ];
    }
}
