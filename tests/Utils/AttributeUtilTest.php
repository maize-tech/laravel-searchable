<?php

use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Support\Facades\DB;
use Maize\Searchable\Utils\AttributeUtil;

it('should return false if an expression is given as attribute', function () {
    $attribute = new Expression('');
    $model = $this->createUser();

    $result = AttributeUtil::isAttribute($model, $attribute);

    expect($result)->toBeFalse();
});

it('should return false if the field given is not a model attribute', function () {
    $attribute = 'age';
    $model = $this->createUser();

    $result = AttributeUtil::isAttribute($model, $attribute);

    expect($result)->toBeFalse();
});

it('should return true if the field given is a model attribute', function () {
    $attribute = 'email';
    $model = $this->createUser();

    $result = AttributeUtil::isAttribute($model, $attribute);

    expect($result)->toBeTrue();
});

it('should return false if an expression is given as relationship', function () {
    $attribute = new Expression('');
    $model = $this->createUser();

    $result = AttributeUtil::isRelationship($model, $attribute);

    expect($result)->toBeFalse();
});

it('should return false if the field given is not a relationship', function () {
    $attribute = 'comments.title';
    $model = $this->createUser();

    $result = AttributeUtil::isRelationship($model, $attribute);

    expect($result)->toBeFalse();
});

it('should return true if the field given is a relationship', function () {
    $attribute = 'team.name';
    $model = $this->createUser();

    $result = AttributeUtil::isRelationship($model, $attribute);

    expect($result)->toBeTrue();
});

it('should return false if an expression is given as json attribute', function () {
    $attribute = new Expression('');
    $model = $this->createUser();

    $result = AttributeUtil::isJsonAttribute($model, $attribute);

    expect($result)->toBeFalse();
});

it('should return false if a relationship is given as json attribute', function () {
    $attribute = 'team.name';
    $model = $this->createUser();

    $result = AttributeUtil::isJsonAttribute($model, $attribute);

    expect($result)->toBeFalse();
});

it('should return true if a json attribute is given', function () {
    $attribute = 'description.*';
    $model = $this->createUser();

    $result = AttributeUtil::isJsonAttribute($model, $attribute);

    expect($result)->toBeTrue();
});

it('should return the unquoted extraction of the attribute name if a json attribute is given', function () {
    $model = $this->createUser();
    $attributeName = 'description';
    $jsonKey = 'en';

    $result = AttributeUtil::formatJsonOperator($model, $attributeName, $jsonKey);

    expect($result)->toEqual("JSON_UNQUOTE(JSON_EXTRACT($attributeName, '$.\"$jsonKey\"'))");
});

it('should return the attribute name casted as text if all attributes selector and postgres db connection are set', function () {
    $model = $this->createUser();
    $attributeName = 'description';
    $jsonKey = '*';

    $connection = app('db.connection');
    $postgresGrammar = new PostgresGrammar($connection);
    $connection->setQueryGrammar($postgresGrammar);

    $result = AttributeUtil::formatJsonOperator($model, $attributeName, $jsonKey);

    expect($result)->toEqual("{$attributeName}::TEXT");
});

it('should return the attribute name with specific key element selector if specific key and postgres db connection are set', function () {
    $model = $this->createUser();
    $attributeName = 'description';
    $jsonKey = 'en';

    $connection = app('db.connection');
    $postgresGrammar = new PostgresGrammar($connection);
    $connection->setQueryGrammar($postgresGrammar);

    $result = AttributeUtil::formatJsonOperator($model, $attributeName, $jsonKey);

    expect($result)->toEqual("{$attributeName}->>'{$jsonKey}'");
});

it('should return the expression if an expression attribute is given', function () {
    $model = $this->createUser();
    $attribute = DB::raw("CONCAT(first_name, ' ', last_name)");

    $result = AttributeUtil::formatAttribute($model, $attribute);

    expect("CONCAT(first_name, ' ', last_name)")->toEqual($result);
});

it('should return the first non null value in a list with the specific operator if json attribute is given', function () {
    $model = $this->createUser();
    $attribute = 'description.*';

    $result = AttributeUtil::formatAttribute($model, $attribute);

    expect($result)->toEqual("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(users.description, '$.\"*\"')),'')");
});

it('should return the associated attribute as query string', function () {
    $model = $this->createUser();
    $attribute = 'first_name';

    $result = AttributeUtil::formatAttribute($model, $attribute);

    expect($result)->toEqual('users.first_name');
});

it('should return the associated relation if a relationship query string is given', function () {
    $model = $this->createUser();
    $attribute = 'team.name';

    $result = AttributeUtil::formatAttribute($model, $attribute);

    expect($result)->toEqual('teams.name');
});
