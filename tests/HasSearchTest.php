<?php

use Maize\Searchable\Tests\Models\Team;
use Maize\Searchable\Tests\Models\User;


it('should retrieve all users without search', function () {
    $this->createUser();
    $this->createUser();
    $this->createUser();

    $users = User::get();

    expect($users)->toHaveCount(3);
});

it('should retrieve all users with empty search', function () {
    $this->createUser();
    $this->createUser();
    $this->createUser();

    $users = User::search('')->get();

    expect($users)->toHaveCount(3);
});

it('should filter users with search', function () {
    $this->createUser([
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'email' => 'mario.rossi@example.com',
    ]);
    $this->createUser();
    $this->createUser();

    $users = User::search('mario')->get();

    expect($users)->toHaveCount(1);
});

it('should order results with match weight', function () {
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

    expect($userEmails)->toEqual(['mario.rossi@example.com', 'giuseppe.rossi@example.com']);
});

it('should order results with attribute weight', function () {
    $this->createUser([
        'first_name' => 'Mario',
    ]);
    $this->createUser([
        'email' => 'mario.rossi@example.com',
    ]);
    $this->createUser();

    $users = User::search('mario')->get();
    $userEmails = $users->pluck('email')->toArray();

    expect($userEmails)->toEqual(['mario.rossi@example.com', 'name.surname@example.com']);
});

it('should apply search to relation attributes', function () {
    $team = $this->createTeam(['name' => 'Test team']);
    $user = $this->createUser(['team_id' => $team->id]);

    $users = User::search('test team')->get();
    $userIds = $users->pluck('id')->toArray();

    expect($userIds)->toEqual([$user->id]);
});

it('should apply search to models with string id', function () {
    $this->createTeam(['name' => 'Wayne Enterprises']);
    $this->createTeam(['name' => 'Diabolik Inc']);
    $this->createTeam(['name' => 'Dunder Mifflin Inc']);

    $teams = Team::search('Dunder')->get();
    $teamNames = $teams->pluck('name')->toArray();

    expect($teamNames)->toEqual(['Dunder Mifflin Inc']);

    $teams = Team::search('Inc')->get();
    $teamNames = $teams->pluck('name')->toArray();

    expect($teamNames)->toEqual(['Diabolik Inc', 'Dunder Mifflin Inc']);
});

it('should not order by search weight when flag is set', function () {
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

    expect($userEmails)->toEqual(['mario.rossi@example.com', 'giuseppe.rossi@example.com']);
});
