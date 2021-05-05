<?php

namespace HFarm\Searchable\Tests\Utils;

use HFarm\Searchable\Tests\Models\User;
use HFarm\Searchable\Tests\TestCase;
use HFarm\Searchable\Utils\Joiner;
use LogicException;

class JoinerTest extends TestCase
{
    /** @test */
    public function it_should_inner_join_relations()
    {
        $sql = 'select * from "users" inner join "teams" on "users"."team_id" = "teams"."id"';

        $user = User::query();
        $userQuery = $user->getQuery();

        Joiner::joinAll($userQuery, $user->getModel(), ['team']);

        $this->assertEquals($sql, $userQuery->toSql());
    }

    /** @test */
    public function it_should_left_join_relations()
    {
        $sql = 'select * from "users" left join "teams" on "users"."team_id" = "teams"."id"';

        $user = User::query();
        $userQuery = $user->getQuery();

        Joiner::leftJoinAll($userQuery, $user->getModel(), ['team']);

        $this->assertEquals($sql, $userQuery->toSql());
    }

    /** @test */
    public function it_should_right_join_relations()
    {
        $sql = 'select * from "users" right join "teams" on "users"."team_id" = "teams"."id"';

        $user = User::query();
        $userQuery = $user->getQuery();

        Joiner::rightJoinAll($userQuery, $user->getModel(), ['team']);

        $this->assertEquals($sql, $userQuery->toSql());
    }

    /** @test */
    public function it_should_join_dot_nested_relations()
    {
        $sql = 'select * from "users" ' .
            'inner join "teams" on "users"."team_id" = "teams"."id" ' .
            'inner join "badges" on "badges"."team_id" = "teams"."id"';

        $user = User::query();
        $userQuery = $user->getQuery();

        Joiner::joinAll($userQuery, $user->getModel(), ['team.badges']);

        $this->assertEquals($sql, $userQuery->toSql());
    }

    /** @test */
    public function it_should_join_morph_to_many_relations()
    {
        $sql = 'select * from "users" ' .
            'inner join "taggables" on "taggables"."taggable_id" = "users"."id" ' .
            'inner join "tags" on "taggables"."tag_id" = "tags"."id" and "taggable_type" = ?';

        $user = User::query();
        $userQuery = $user->getQuery();

        Joiner::joinAll($userQuery, $user->getModel(), ['tags']);

        $this->assertEquals($sql, $userQuery->toSql());
    }

    /** @test */
    public function it_should_not_join_morph_to_relations()
    {
        $this->expectException(LogicException::class);

        $user = User::query();
        $userQuery = $user->getQuery();

        Joiner::joinAll($userQuery, $user->getModel(), ['morphs']);
    }

    /** @test */
    public function it_should_join_relations_with_soft_delete()
    {
        $sql = 'select * from "users" ' .
            'inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."deleted_at" is null';

        $user = User::query();
        $userQuery = $user->getQuery();

        Joiner::joinAll($userQuery, $user->getModel(), ['posts']);

        $this->assertEquals($sql, $userQuery->toSql());
    }
}
