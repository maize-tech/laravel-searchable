<?php

namespace Maize\Searchable\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Maize\Searchable\Utils\AttributeUtil;

trait AddsSearchTermToQuery
{
    /**
     * Queries the given term within the given attributes.
     *
     * @param  string|Expression  $attribute
     */
    protected function searchTerm(Builder $query, $attribute, float $weight, string $searchTerm): void
    {
        $attributeField = AttributeUtil::formatAttribute($this->getModel(), $attribute);

        $this->querySearchTerm($query, $attributeField, $searchTerm, $weight);
    }

    /**
     * Queries the given search term against the given attribute.
     */
    protected function querySearchTerm(Builder $query, string $attributeField, string $term, float $weight): void
    {
        $sql = "LOWER($attributeField) LIKE ?";

        $query->orWhereRaw($sql, $term);

        $this->addSearchWeight($sql, $weight, $term);
    }

    /**
     * Add the given search term to the weights list.
     */
    protected function addSearchWeight(string $sql, float $weight, string $searchTerm): void
    {
        $this->searchWeights->add([
            'query' => "(CASE WHEN ($sql) THEN $weight ELSE 0 END)",
            'value' => $searchTerm,
        ]);
    }
}
