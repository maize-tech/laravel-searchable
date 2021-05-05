<?php

namespace HFarm\Searchable\Tests;

use HFarm\Searchable\Tests\Models\User;

class HasSearchTest extends TestCase
{
    /** @test */
    public function it_should_retrieve_all_users_without_search()
    {
        $this->createUser();
        $this->createUser();
        $this->createUser();

        $users = User::get();

        $this->assertCount(3, $users);
    }

    /** @test */
    public function it_should_retrieve_all_users_with_empty_search()
    {
        $this->createUser();
        $this->createUser();
        $this->createUser();

        $users = User::search('')->get();

        $this->assertCount(3, $users);
    }

    /** @test */
    public function it_should_filter_users_with_search()
    {
        $this->createUser([
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => 'mario.rossi@example.com',
        ]);
        $this->createUser();
        $this->createUser();

        $users = User::search('mario')->get();

        $this->assertCount(1, $users);
    }

    /** @test */
    public function it_should_order_results_with_match_weight()
    {
        $this->createUser([
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => 'mario.rossi@example.com',
        ]);
        $this->createUser([
            'first_name' => 'Giuseppe',
            'last_name' => 'Rossi',
            'email' => 'giuseppe.rossi@example.com',
        ]);
        $this->createUser();

        $users = User::search('mario rossi')->get();
        $userEmails = $users->pluck('email')->toArray();

        $this->assertEquals(['mario.rossi@example.com', 'giuseppe.rossi@example.com'], $userEmails);
    }

    /** @test */
    public function it_should_order_results_with_attribute_weight()
    {
        $this->createUser([
            'first_name' => 'Mario',
        ]);
        $this->createUser([
            'email' => 'mario.rossi@example.com',
        ]);
        $this->createUser();

        $users = User::search('mario')->get();
        $userEmails = $users->pluck('email')->toArray();

        $this->assertEquals(['mario.rossi@example.com', 'name.surname@example.com'], $userEmails);
    }

    /** @test */
    public function it_should_apply_search_to_relation_attributes()
    {
        $team = $this->createTeam(['name' => 'Test team']);
        $user = $this->createUser(['team_id' => $team->id]);

        $users = User::search('test team')->get();
        $userIds = $users->pluck('id')->toArray();

        $this->assertEquals([$user->id], $userIds);
    }

    /** @test */
    public function it_should_not_order_by_search_weight_when_flag_is_set()
    {
        $this->createUser([
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => 'mario.rossi@example.com',
        ]);
        $this->createUser([
            'first_name' => 'Giuseppe',
            'last_name' => 'Rossi',
            'email' => 'giuseppe.rossi@example.com',
        ]);
        $this->createUser();

        $users = User::search('giuseppe rossi', false, false)->get();
        $userEmails = $users->pluck('email')->toArray();

        $this->assertEquals(['mario.rossi@example.com', 'giuseppe.rossi@example.com'], $userEmails);
    }
}
