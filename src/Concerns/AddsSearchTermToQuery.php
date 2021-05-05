<?php

namespace HFarm\Searchable\Concerns;

use HFarm\Searchable\Utils\AttributeUtil;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;

trait AddsSearchTermToQuery
{
    /**
     * Queries the given term within the given attributes.
     *
     * @param Builder $query
     * @param string|Expression $attribute
     * @param float $weight
     * @param string $searchTerm
     * @return void
     */
    protected function searchTerm(Builder $query, $attribute, float $weight, string $searchTerm): void
    {
        $attributeField = AttributeUtil::formatAttribute($this->getModel(), $attribute);

        $this->querySearchTerm($query, $attributeField, $searchTerm, $weight);
    }

    /**
     * Queries the given search term against the given attribute.
     *
     * @param Builder $query
     * @param string $attributeField
     * @param string $term
     * @param float $weight
     * @return void
     */
    protected function querySearchTerm(Builder $query, string $attributeField, string $term, float $weight): void
    {
        $sql = "LOWER($attributeField) LIKE ?";

        $query->orWhereRaw($sql, $term);

        $this->addSearchWeight($sql, $weight, $term);
    }

    /**
     * Add the given search term to the weights list.
     *
     * @param string $sql
     * @param float $weight
     * @param string $searchTerm
     * @return void
     */
    protected function addSearchWeight(string $sql, float $weight, string $searchTerm): void
    {
        $this->searchWeights->add([
            'query' => "(CASE WHEN ($sql) THEN $weight ELSE 0 END)",
            'value' => $searchTerm,
        ]);
    }
}
