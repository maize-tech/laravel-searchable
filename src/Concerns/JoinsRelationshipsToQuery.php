<?php

namespace Maize\Searchable\Concerns;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Maize\Searchable\Utils\AttributeUtil;
use Maize\Searchable\Utils\Joiner;

trait JoinsRelationshipsToQuery
{
    /**
     * Lefts joins model's relationships to the given query.
     *
     * @param Builder $query
     * @param string|null $as
     * @return static
     */
    protected function joinRelationships(Builder $query, ?string $as = null): self
    {
        Joiner::leftJoinAll($query, $this->getModel(), $this->getRelationships(), $as);

        return $this;
    }

    /**
     * Retrieves the list of relationships with at least one searchable attribute.
     *
     * @return array
     */
    protected function getRelationships(): array
    {
        return $this->searchableAttributes
            ->map
            ->getAttribute()
            ->filter(fn ($attribute) => AttributeUtil::isRelationship($this->getModel(), $attribute))
            ->map(fn ($attribute) => Arr::first(explode('.', $attribute)))
            ->unique()
            ->toArray();
    }
}
