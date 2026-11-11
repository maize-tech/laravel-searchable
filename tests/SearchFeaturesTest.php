<?php

use Maize\Searchable\Tests\Models\Article;
use Maize\Searchable\Tests\Models\Document;

beforeEach(function () {
    Article::$searchableAttributes = [];
});

it('should search a single json key', function () {
    Article::$searchableAttributes = ['body.en'];

    Article::create(['title' => 'First', 'body' => ['en' => 'Green apple', 'it' => 'Mela verde']]);
    Article::create(['title' => 'Second', 'body' => ['en' => 'Red cherry', 'it' => 'Ciliegia rossa']]);

    expect(Article::search('apple')->pluck('title')->all())->toEqual(['First']);
    expect(Article::search('mela')->pluck('title')->all())->toEqual([]);
});

it('should ignore null json columns and missing json keys', function () {
    Article::$searchableAttributes = ['body.en', 'body.*'];

    Article::create(['title' => 'First', 'body' => null]);
    Article::create(['title' => 'Second', 'body' => ['it' => 'Mela verde']]);
    Article::create(['title' => 'Third', 'body' => ['en' => 'Green apple']]);

    expect(Article::search('apple')->pluck('title')->all())->toEqual(['Third']);
});

it('should search all top level json values with the wildcard selector', function () {
    Article::$searchableAttributes = ['body.*'];

    Article::create(['title' => 'First', 'body' => ['en' => 'Green apple', 'it' => 'Mela verde']]);
    Article::create(['title' => 'Second', 'body' => ['en' => 'Red cherry', 'it' => 'Ciliegia rossa']]);

    expect(Article::search('apple')->pluck('title')->all())->toEqual(['First']);
    expect(Article::search('ciliegia')->pluck('title')->all())->toEqual(['Second']);
    expect(Article::search('banana')->pluck('title')->all())->toEqual([]);
});

it('should search belongs to relationship attributes', function () {
    Article::$searchableAttributes = ['team.name'];

    $team = $this->createTeam(['name' => 'Rocket team']);
    Article::create(['title' => 'First', 'team_id' => $team->id]);
    Article::create(['title' => 'Second', 'team_id' => $this->createTeam()->id]);
    Article::create(['title' => 'Third']);

    expect(Article::search('rocket')->pluck('title')->all())->toEqual(['First']);
});

it('should search has many relationship attributes without duplicating results', function () {
    Article::$searchableAttributes = ['comments.content'];

    $first = Article::create(['title' => 'First']);
    $first->comments()->create(['content' => 'Great read']);
    $first->comments()->create(['content' => 'Great pictures']);
    $second = Article::create(['title' => 'Second']);
    $second->comments()->create(['content' => 'Too long']);
    Article::create(['title' => 'Third']);

    expect(Article::search('great')->pluck('title')->all())->toEqual(['First']);
    expect(Article::search('long')->pluck('title')->all())->toEqual(['Second']);
});

it('should search belongs to many relationship attributes', function () {
    Article::$searchableAttributes = ['labels.name'];

    $first = Article::create(['title' => 'First']);
    $label = $first->labels()->create(['name' => 'Urgent']);
    $second = Article::create(['title' => 'Second']);
    $second->labels()->attach($label);
    $second->labels()->create(['name' => 'Draft']);
    Article::create(['title' => 'Third']);

    expect(Article::search('urgent')->pluck('title')->sort()->values()->all())->toEqual(['First', 'Second']);
    expect(Article::search('draft')->pluck('title')->all())->toEqual(['Second']);
});

it('should search json keys of relationship attributes', function () {
    Article::$searchableAttributes = ['comments.meta.note', 'labels.description.*'];

    $first = Article::create(['title' => 'First']);
    $first->comments()->create(['content' => 'Comment', 'meta' => ['note' => 'Pinned by author', 'other' => 'Hidden']]);
    $second = Article::create(['title' => 'Second']);
    $second->labels()->create(['name' => 'Label', 'description' => ['en' => 'Very important', 'it' => 'Molto importante']]);
    Article::create(['title' => 'Third']);

    expect(Article::search('pinned')->pluck('title')->all())->toEqual(['First']);
    expect(Article::search('hidden')->pluck('title')->all())->toEqual([]);
    expect(Article::search('molto')->pluck('title')->all())->toEqual(['Second']);
});

it('should match models containing any of the search terms', function () {
    Article::$searchableAttributes = ['title', 'subtitle'];

    Article::create(['title' => 'Green apple', 'subtitle' => 'Fruit']);
    Article::create(['title' => 'Red cherry', 'subtitle' => 'Fruit']);
    Article::create(['title' => 'Green salad', 'subtitle' => 'Vegetable']);

    expect(Article::search('apple cherry')->pluck('title')->sort()->values()->all())
        ->toEqual(['Green apple', 'Red cherry']);
    expect(Article::search('green fruit')->pluck('title')->first())->toEqual('Green apple');
    expect(Article::search('green fruit')->pluck('title')->sort()->values()->all())
        ->toEqual(['Green apple', 'Green salad', 'Red cherry']);
});

it('should match quoted search terms as a single phrase', function () {
    Article::$searchableAttributes = ['title'];

    Article::create(['title' => 'Green apple']);
    Article::create(['title' => 'Apple green']);

    expect(Article::search('"green apple"')->pluck('title')->all())->toEqual(['Green apple']);
});

it('should match models containing all the search terms when chaining searches', function () {
    Article::$searchableAttributes = ['title', 'subtitle'];

    Article::create(['title' => 'Green apple', 'subtitle' => 'Fruit']);
    Article::create(['title' => 'Red cherry', 'subtitle' => 'Fruit']);
    Article::create(['title' => 'Green salad', 'subtitle' => 'Vegetable']);

    $titles = Article::search('green')->search('fruit')->pluck('title')->all();

    expect($titles)->toEqual(['Green apple']);
});

it('should match ascii characters case insensitively', function () {
    Article::$searchableAttributes = ['title', 'body.en'];

    Article::create(['title' => 'GREEN Apple']);
    Article::create(['title' => 'Second', 'body' => ['en' => 'Red CHERRY']]);

    expect(Article::search('green')->pluck('title')->all())->toEqual(['GREEN Apple']);
    expect(Article::search('aPPle')->pluck('title')->all())->toEqual(['GREEN Apple']);
    expect(Article::search('Cherry')->pluck('title')->all())->toEqual(['Second']);
});

it('should order results by the sum of matched weights', function () {
    Article::$searchableAttributes = ['title' => 5, 'subtitle' => 1, 'body.en' => 2];

    Article::create(['title' => 'Subtitle only', 'subtitle' => 'An apple']);
    Article::create(['title' => 'Json only', 'body' => ['en' => 'An apple']]);
    Article::create(['title' => 'Apple title only']);
    Article::create(['title' => 'Apple everywhere', 'subtitle' => 'An apple', 'body' => ['en' => 'An apple']]);
    Article::create(['title' => 'No match']);

    expect(Article::search('apple')->pluck('title')->all())->toEqual([
        'Apple everywhere',
        'Apple title only',
        'Json only',
        'Subtitle only',
    ]);
});

it('should keep models with the same weight after models with a higher weight', function () {
    Article::$searchableAttributes = ['title' => 5, 'subtitle' => 1];

    Article::create(['title' => 'First tie', 'subtitle' => 'An apple']);
    Article::create(['title' => 'Second tie', 'subtitle' => 'An apple']);
    Article::create(['title' => 'Apple', 'subtitle' => 'An apple']);
    Article::create(['title' => 'Third tie', 'subtitle' => 'An apple']);

    $titles = Article::search('apple')->pluck('title');

    expect($titles->first())->toEqual('Apple');
    expect($titles->skip(1)->sort()->values()->all())->toEqual(['First tie', 'Second tie', 'Third tie']);
});

it('should search plain columns and json keys of models with string id', function () {
    Document::create(['code' => 'DOC-1', 'payload' => ['name' => 'Annual report', 'description' => 'Numbers of the year']]);
    Document::create(['code' => 'DOC-2', 'payload' => ['name' => 'Meeting notes', 'description' => 'Notes about the annual report']]);
    Document::create(['code' => 'REPORT-3', 'payload' => ['name' => 'Summary', 'description' => 'Short summary']]);
    Document::create(['code' => 'DOC-4', 'payload' => null]);

    expect(Document::search('report')->pluck('code')->all())->toEqual(['REPORT-3', 'DOC-1', 'DOC-2']);
    expect(Document::search('meeting')->pluck('code')->all())->toEqual(['DOC-2']);
    expect(Document::search('missing')->pluck('code')->all())->toEqual([]);
});
