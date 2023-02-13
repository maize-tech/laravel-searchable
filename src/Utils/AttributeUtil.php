<?php

namespace Maize\Searchable\Utils;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class AttributeUtil
{
    const ALL_ATTRIBUTES_SELECTOR = '*';

    /**
     * Checks whether the given field is an attribute or not.
     *
     * @param Model $model
     * @param string|Expression $attribute
     * @return bool
     */
    public static function isAttribute(Model $model, $attribute): bool
    {
        if ($attribute instanceof Expression) {
            return false;
        }

        $tableName = $model->getTable();

        return Schema::hasColumn(
            $tableName,
            explode('.', $attribute)[0]
        );
    }

    /**
     * Checks whether the given field is a relationship or not.
     *
     * @param Model $model
     * @param string|Expression $attribute
     * @return bool
     */
    public static function isRelationship(Model $model, $attribute): bool
    {
        if ($attribute instanceof Expression) {
            return false;
        }

        $relationship = explode('.', $attribute)[0];

        return method_exists($model, $relationship);
    }

    /**
     * Checks whether the given field is a json
     * attribute or not.
     *
     * @param Model $model
     * @param string|Expression $attribute
     * @return bool
     */
    public static function isJsonAttribute(Model $model, $attribute): bool
    {
        if ($attribute instanceof Expression) {
            return false;
        }

        $count = self::isRelationship($model, $attribute) ? 2 : 1;

        return count(explode('.', $attribute)) > $count;
    }

    /**
     * Prepares the given attribute and returns the
     * associated query string.
     *
     * @param Model $model
     * @param string|Expression $attribute
     * @return string
     */
    public static function formatAttribute(Model $model, $attribute): string
    {
        if ($attribute instanceof Expression) {
            $reflectionMethod = new \ReflectionMethod(Expression::class, 'getValue');

            return $reflectionMethod->invokeArgs($attribute, [
                'grammar' => $model::query()->getQuery()->getGrammar()
            ]);
        }

        $attributeName = self::formatAttributeName($model, $attribute);

        if (self::isJsonAttribute($model, $attribute)) {
            $jsonKey = Arr::last(explode('.', $attribute));
            $jsonOperator = self::formatJsonOperator($model, $attributeName, $jsonKey);

            return "COALESCE($jsonOperator,'')";
        }

        return $attributeName;
    }

    /**
     * Formats the driver-specific json operator
     * to extract the given json key.
     *
     * @param Model $model
     * @param string $attributeName
     * @param string $jsonKey
     * @return string
     */
    public static function formatJsonOperator(Model $model, string $attributeName, string $jsonKey): string
    {
        $grammar = $model::query()->getQuery()->getGrammar();

        if ($grammar instanceof PostgresGrammar) {
            if ($jsonKey === self::ALL_ATTRIBUTES_SELECTOR) {
                return "$attributeName::TEXT";
            }

            return "$attributeName->>'$jsonKey'";
        }

        return "JSON_UNQUOTE(JSON_EXTRACT($attributeName, '$.\"$jsonKey\"'))";
    }

    /**
     * Prepares the given attribute and returns the
     * associated field name.
     *
     * @param Model $model
     * @param string $attribute
     * @return string
     */
    protected static function formatAttributeName(Model $model, string $attribute): string
    {
        if (self::isRelationship($model, $attribute)) {
            [$relationship, $field] = explode('.', $attribute);
            $relationTable = $model->{$relationship}()->getRelated()->getTable();

            return "$relationTable.$field";
        }

        $tableName = $model->getTable();
        $field = Arr::first(explode('.', $attribute));

        return "$tableName.$field";
    }
}
