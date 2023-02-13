<?php

namespace Maize\Searchable\Tests\Utils;

use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Support\Facades\DB;
use Maize\Searchable\Tests\TestCase;
use Maize\Searchable\Utils\AttributeUtil;

class AttributeUtilTest extends TestCase
{
    /** @test */
    public function it_should_return_false_if_an_expression_is_given_as_attribute()
    {
        $attribute = new Expression('');
        $model = $this->createUser();

        $result = AttributeUtil::isAttribute($model, $attribute);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_should_return_false_if_the_field_given_is_not_a_model_attribute()
    {
        $attribute = 'age';
        $model = $this->createUser();

        $result = AttributeUtil::isAttribute($model, $attribute);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_should_return_true_if_the_field_given_is_a_model_attribute()
    {
        $attribute = 'email';
        $model = $this->createUser();

        $result = AttributeUtil::isAttribute($model, $attribute);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_should_return_false_if_an_expression_is_given_as_relationship()
    {
        $attribute = new Expression('');
        $model = $this->createUser();

        $result = AttributeUtil::isRelationship($model, $attribute);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_should_return_false_if_the_field_given_is_not_a_relationship()
    {
        $attribute = 'comments.title';
        $model = $this->createUser();

        $result = AttributeUtil::isRelationship($model, $attribute);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_should_return_true_if_the_field_given_is_a_relationship()
    {
        $attribute = 'team.name';
        $model = $this->createUser();

        $result = AttributeUtil::isRelationship($model, $attribute);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_should_return_false_if_an_expression_is_given_as_json_attribute()
    {
        $attribute = new Expression('');
        $model = $this->createUser();

        $result = AttributeUtil::isJsonAttribute($model, $attribute);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_should_return_false_if_a_relationship_is_given_as_json_attribute()
    {
        $attribute = 'team.name';
        $model = $this->createUser();

        $result = AttributeUtil::isJsonAttribute($model, $attribute);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_should_return_true_if_a_json_attribute_is_given()
    {
        $attribute = 'description.*';
        $model = $this->createUser();

        $result = AttributeUtil::isJsonAttribute($model, $attribute);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_should_return_the_unquoted_extraction_of_the_attribute_name_if_a_json_attribute_is_given()
    {
        $model = $this->createUser();
        $attributeName = 'description';
        $jsonKey = 'en';

        $result = AttributeUtil::formatJsonOperator($model, $attributeName, $jsonKey);

        $this->assertEquals("JSON_UNQUOTE(JSON_EXTRACT($attributeName, '$.\"$jsonKey\"'))", $result);
    }

    /** @test */
    public function it_should_return_the_attribute_name_casted_as_text_if_all_attributes_selector_and_postgres_db_connection_are_set()
    {
        $model = $this->createUser();
        $attributeName = 'description';
        $jsonKey = '*';

        app('db.connection')->setQueryGrammar(new PostgresGrammar);

        $result = AttributeUtil::formatJsonOperator($model, $attributeName, $jsonKey);

        $this->assertEquals("{$attributeName}::TEXT", $result);
    }

    /** @test */
    public function it_should_return_the_attribute_name_with_specific_key_element_selector_if_specific_key_and_postgres_db_connection_are_set()
    {
        $model = $this->createUser();
        $attributeName = 'description';
        $jsonKey = 'en';

        app('db.connection')->setQueryGrammar(new PostgresGrammar);

        $result = AttributeUtil::formatJsonOperator($model, $attributeName, $jsonKey);

        $this->assertEquals("{$attributeName}->>'{$jsonKey}'", $result);
    }

    /** @test */
    public function it_should_return_the_expression_if_an_expression_attribute_is_given()
    {
        $model = $this->createUser();
        $attribute = DB::raw("CONCAT(first_name, ' ', last_name)");

        $result = AttributeUtil::formatAttribute($model, $attribute);

        $this->assertEquals($result, "CONCAT(first_name, ' ', last_name)");
    }

    /** @test */
    public function it_should_return_the_first_non_null_value_in_a_list_with_the_specific_operator_if_json_attribute_is_given()
    {
        $model = $this->createUser();
        $attribute = 'description.*';

        $result = AttributeUtil::formatAttribute($model, $attribute);

        $this->assertEquals("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(users.description, '$.\"*\"')),'')", $result);
    }

    /** @test */
    public function it_should_return_the_associated_attribute_as_query_string()
    {
        $model = $this->createUser();
        $attribute = 'first_name';

        $result = AttributeUtil::formatAttribute($model, $attribute);

        $this->assertEquals('users.first_name', $result);
    }

    /** @test */
    public function it_should_return_the_associated_relation_if_a_relationship_query_string_is_given()
    {
        $model = $this->createUser();
        $attribute = 'team.name';

        $result = AttributeUtil::formatAttribute($model, $attribute);

        $this->assertEquals('teams.name', $result);
    }
}
