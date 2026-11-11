<?php

namespace Maize\Searchable\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Maize\Searchable\SearchableServiceProvider;
use Maize\Searchable\Tests\Models\Team;
use Maize\Searchable\Tests\Models\User;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
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
        $connection = env('DB_CONNECTION', 'sqlite');

        $app['config']->set('database.default', $connection);

        if ($connection === 'sqlite') {
            $app['config']->set('database.connections.sqlite', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        }

        include_once __DIR__.'/../database/migrations/create_teams_table.php.stub';
        (new \CreateTeamsTable)->down();
        (new \CreateTeamsTable)->up();

        include_once __DIR__.'/../database/migrations/create_users_table.php.stub';
        (new \CreateUsersTable)->down();
        (new \CreateUsersTable)->up();

        $this->createSearchTables();
    }

    /**
     * Creates the tables used to test json and relationship searches.
     */
    protected function createSearchTables(): void
    {
        foreach (['articles', 'comments', 'labels', 'article_label', 'documents'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('team_id')->nullable();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->json('body')->nullable();
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id');
            $table->string('content');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('labels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('description')->nullable();
            $table->timestamps();
        });

        Schema::create('article_label', function (Blueprint $table) {
            $table->foreignId('article_id');
            $table->foreignId('label_id');
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code');
            $table->json('payload')->nullable();
            $table->timestamps();
        });
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
