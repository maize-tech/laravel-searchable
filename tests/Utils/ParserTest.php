<?php

use Maize\Searchable\Utils\Parser;

it('should trim the input string', function () {
    $query = ' test ';

    $result = Parser::parseQuery($query, false);

    expect($result)->toEqual(['test']);
});

it('should lowercase the input string', function () {
    $query = 'TEST';

    $result = Parser::parseQuery($query, false);

    expect($result)->toEqual(['test']);
});

it('should split the input string', function () {
    $query = 'split test';

    $result = Parser::parseQuery($query, false);

    expect($result)->toEqual(['split', 'test']);
});

it('should add wildcards if searching fulltext', function () {
    $query = 'wildcard test';

    $result = Parser::parseQuery($query, true);

    expect($result)->toEqual(['%wildcard%', '%test%']);
});

it('should not add wildcards if not searching fulltext', function () {
    $query = 'no wildcard test';

    $result = Parser::parseQuery($query, false);

    expect($result)->toEqual(['no', 'wildcard', 'test']);
});
