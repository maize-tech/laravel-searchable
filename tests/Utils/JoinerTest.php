<?php

use Maize\Searchable\Tests\Models\User;
use Maize\Searchable\Utils\Joiner;


it('should inner join relations', function () {
    $sql = 'select * from "users" inner join "teams" on "users"."team_id" = "teams"."id"';

    $user = User::query();
    $userQuery = $user->getQuery();

    Joiner::joinAll($userQuery, $user->getModel(), ['team']);

    expect($userQuery->toSql())->toEqual($sql);
});

it('should left join relations', function () {
    $sql = 'select * from "users" left join "teams" on "users"."team_id" = "teams"."id"';

    $user = User::query();
    $userQuery = $user->getQuery();

    Joiner::leftJoinAll($userQuery, $user->getModel(), ['team']);

    expect($userQuery->toSql())->toEqual($sql);
});

it('should right join relations', function () {
    $sql = 'select * from "users" right join "teams" on "users"."team_id" = "teams"."id"';

    $user = User::query();
    $userQuery = $user->getQuery();

    Joiner::rightJoinAll($userQuery, $user->getModel(), ['team']);

    expect($userQuery->toSql())->toEqual($sql);
});

it('should join dot nested relations', function () {
    $sql = 'select * from "users" ' .
        'inner join "teams" on "users"."team_id" = "teams"."id" ' .
        'inner join "badges" on "badges"."team_id" = "teams"."id"';

    $user = User::query();
    $userQuery = $user->getQuery();

    Joiner::joinAll($userQuery, $user->getModel(), ['team.badges']);

    expect($userQuery->toSql())->toEqual($sql);
});

it('should join morph to many relations', function () {
    $sql = 'select * from "users" ' .
        'inner join "taggables" on "taggables"."taggable_id" = "users"."id" ' .
        'inner join "tags" on "taggables"."tag_id" = "tags"."id" and "taggable_type" = ?';

    $user = User::query();
    $userQuery = $user->getQuery();

    Joiner::joinAll($userQuery, $user->getModel(), ['tags']);

    expect($userQuery->toSql())->toEqual($sql);
});

it('should not join morph to relations', function () {
    $this->expectException(LogicException::class);

    $user = User::query();
    $userQuery = $user->getQuery();

    Joiner::joinAll($userQuery, $user->getModel(), ['morphs']);
});

it('should join relations with soft delete', function () {
    $sql = 'select * from "users" ' .
        'inner join "posts" on "posts"."user_id" = "users"."id" and "posts"."deleted_at" is null';

    $user = User::query();
    $userQuery = $user->getQuery();

    Joiner::joinAll($userQuery, $user->getModel(), ['posts']);

    expect($userQuery->toSql())->toEqual($sql);
});
