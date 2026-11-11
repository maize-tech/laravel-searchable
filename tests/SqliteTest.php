<?php

use Illuminate\Support\Facades\DB;
use Maize\Searchable\SearchBuilder;
use Maize\Searchable\Tests\Models\Article;

beforeEach(function () {
    if (DB::connection()->getDriverName() !== 'sqlite') {
        $this->markTestSkipped('This test only runs on SQLite.');
    }

    Article::$searchableAttributes = [];
});

function sqliteSearchSql(array $attributes, string $search): string
{
    return SearchBuilder::for(Article::query())
        ->withSearchableAttributes($attributes)
        ->search($search)
        ->toSql();
}

it('should generate the sqlite query for a single json key', function () {
    $field = "LOWER(COALESCE(JSON_EXTRACT(articles.body, '$.\"en\"'),'')) LIKE ?";

    expect(sqliteSearchSql(['body.en'], 'apple'))->toEqual(
        'select * from "articles" '.
        "where ($field) ".
        "order by (select (CASE WHEN ($field) THEN 1 ELSE 0 END) ".
        'from "articles" as "sw" where "sw"."id" = "articles"."id" limit 1) desc'
    );
});

it('should generate the sqlite query for the json wildcard selector', function () {
    $field = "LOWER(COALESCE((SELECT GROUP_CONCAT(json_each.value, ' ') FROM JSON_EACH(articles.body)),'')) LIKE ?";

    expect(sqliteSearchSql(['body.*'], 'apple'))->toEqual(
        'select * from "articles" '.
        "where ($field) ".
        "order by (select (CASE WHEN ($field) THEN 1 ELSE 0 END) ".
        'from "articles" as "sw" where "sw"."id" = "articles"."id" limit 1) desc'
    );
});

it('should generate the sqlite query for multiple terms and weights', function () {
    expect(sqliteSearchSql(['title' => 5, 'subtitle' => 2.5], 'green apple'))->toEqual(
        'select * from "articles" '.
        'where (LOWER(articles.title) LIKE ? or LOWER(articles.title) LIKE ? '.
        'or LOWER(articles.subtitle) LIKE ? or LOWER(articles.subtitle) LIKE ?) '.
        'order by (select (CASE WHEN (LOWER(articles.title) LIKE ?) THEN 5 ELSE 0 END)'.
        '+(CASE WHEN (LOWER(articles.title) LIKE ?) THEN 5 ELSE 0 END)'.
        '+(CASE WHEN (LOWER(articles.subtitle) LIKE ?) THEN 2.5 ELSE 0 END)'.
        '+(CASE WHEN (LOWER(articles.subtitle) LIKE ?) THEN 2.5 ELSE 0 END) '.
        'from "articles" as "sw" where "sw"."id" = "articles"."id" limit 1) desc'
    );
});

it('should search raw expressions on sqlite', function () {
    Article::$searchableAttributes = [
        DB::raw("articles.title || ' ' || COALESCE(articles.subtitle, '')"),
    ];

    Article::create(['title' => 'Green', 'subtitle' => 'Apple']);
    Article::create(['title' => 'Apple', 'subtitle' => 'Green']);

    expect(Article::search('"green apple"')->pluck('title')->all())->toEqual(['Green']);
});

it('should not search nested json values or json keys with the wildcard selector on sqlite', function () {
    Article::$searchableAttributes = ['body.*'];

    Article::create(['title' => 'First', 'body' => ['en' => 'Green apple', 'nested' => ['deep' => 'Perché'], 'list' => ['one', 'two'], 'count' => 42]]);

    expect(Article::search('two')->pluck('title')->all())->toEqual(['First']);
    expect(Article::search('42')->pluck('title')->all())->toEqual(['First']);
    expect(Article::search('nested')->pluck('title')->all())->toEqual([]);
    expect(Article::search('perché')->pluck('title')->all())->toEqual([]);
});

it('should match lowercase non ascii characters on sqlite', function () {
    Article::$searchableAttributes = ['title', 'body.en', 'body.*'];

    Article::create(['title' => 'Città']);
    Article::create(['title' => 'Second', 'body' => ['en' => 'Università']]);

    expect(Article::search('città')->pluck('title')->all())->toEqual(['Città']);
    expect(Article::search('CITTÀ')->pluck('title')->all())->toEqual(['Città']);
    expect(Article::search('università')->pluck('title')->all())->toEqual(['Second']);
});

it('should be accent sensitive on sqlite', function () {
    Article::$searchableAttributes = ['title'];

    Article::create(['title' => 'Città']);

    expect(Article::search('citta')->pluck('title')->all())->toEqual([]);
});

it('should not match stored uppercase non ascii characters on sqlite', function () {
    Article::$searchableAttributes = ['title'];

    Article::create(['title' => 'ÀBC']);

    expect(Article::search('àbc')->pluck('title')->all())->toEqual([]);
    expect(Article::search('ÀBC')->pluck('title')->all())->toEqual([]);
    expect(Article::search('bc')->pluck('title')->all())->toEqual(['ÀBC']);
});

it('should not match escaped non ascii characters when searching a whole json column on sqlite', function () {
    Article::$searchableAttributes = ['body'];

    Article::create(['title' => 'First', 'body' => ['en' => 'Green città']]);

    expect(Article::search('green')->pluck('title')->all())->toEqual(['First']);
    expect(Article::search('città')->pluck('title')->all())->toEqual([]);
});
