<?php

namespace Maize\Searchable\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Maize\Searchable\SearchableServiceProvider;
use Maize\Searchable\Tests\Models\Team;
use Maize\Searchable\Tests\Models\User;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Maize\\Searchable\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }
    protected function getPackageProviders($app)
    {
        return [
            SearchableServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        include_once __DIR__.'/../database/migrations/create_teams_table.php.stub';
        (new \CreateTeamsTable())->up();

        include_once __DIR__.'/../database/migrations/create_users_table.php.stub';
        (new \CreateUsersTable())->up();
    }

    public function createUser(array $attrs = [])
    {
        return User::factory()->create($attrs);
    }

    public function createTeam(array $attrs = [])
    {
        return Team::factory()->create($attrs);
    }
}
