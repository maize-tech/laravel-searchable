<?php

namespace HFarm\Searchable\Tests;

use HFarm\Searchable\SearchableServiceProvider;
use HFarm\Searchable\Tests\Models\Team;
use HFarm\Searchable\Tests\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
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
    }

    protected function setUpDatabase($app)
    {
        $app['db']->connection()->getSchemaBuilder()->create('teams', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->json('description');
            $table->timestamps();

            $table->foreign('team_id')->references('id')->on('teams');
        });
    }

    public function createUser(array $attrs = [])
    {
        $user = new User();

        $user->forceFill(array_merge([
            'first_name' => 'Name',
            'last_name' => 'Surname',
            'email' => 'name.surname@example.com',
            'description' => '{ "en": "Just a random guy" }',
            'team_id' => new Team(['name' => 'h-farm']),
        ], $attrs))->save();

        return $user->fresh();
    }

    public function createTeam(array $attrs = [])
    {
        $team = new Team();

        $team->forceFill(array_merge([
            'name' => 'Name',
        ], $attrs))->save();

        return $team->fresh();
    }
}
