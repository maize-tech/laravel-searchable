<?php

namespace HFarm\Searchable\Tests\Utils;

use HFarm\Searchable\Tests\TestCase;
use HFarm\Searchable\Utils\Parser;

class ParserTest extends TestCase
{
    /** @test */
    public function it_should_trim_the_input_string()
    {
        $query = ' test ';

        $result = Parser::parseQuery($query, false);

        $this->assertEquals(['test'], $result);
    }

    /** @test */
    public function it_should_lowercase_the_input_string()
    {
        $query = 'TEST';

        $result = Parser::parseQuery($query, false);

        $this->assertEquals(['test'], $result);
    }

    /** @test */
    public function it_should_split_the_input_string()
    {
        $query = 'split test';

        $result = Parser::parseQuery($query, false);

        $this->assertEquals(['split', 'test'], $result);
    }

    /** @test */
    public function it_should_add_wildcards_if_searching_fulltext()
    {
        $query = 'wildcard test';

        $result = Parser::parseQuery($query, true);

        $this->assertEquals(['%wildcard%', '%test%'], $result);
    }

    /** @test */
    public function it_should_not_add_wildcards_if_not_searching_fulltext()
    {
        $query = 'no wildcard test';

        $result = Parser::parseQuery($query, false);

        $this->assertEquals(['no', 'wildcard', 'test'], $result);
    }
}
