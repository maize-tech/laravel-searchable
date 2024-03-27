<?php

namespace Maize\Searchable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait HasSearch
{
    /**
     * Get the model's searchable attributes.
     */
    abstract public function getSearchableAttributes(): array;

    /**
     * Retrieves the models matching the given query string.
     * If orderByWeight is set to true, orders the list by the
     * weight of each matching model attributes.
     */
    public function scopeSearch(Builder $query, string $search, bool $orderByWeight = true): void
    {
        $keyType = $query->getModel()->getKeyType();
        $keyName = $query->getModel()->getQualifiedKeyName();
        $keys = $this->applySearch($query, $search, $keyName);

        if (in_array($keyType, ['int', 'integer'])) {
            $query->whereIntegerInRaw($keyName, $keys);
        } else {
            $query->whereIn($keyName, $keys);
        }

        $query->when(
            $keys->isNotEmpty() && $orderByWeight,
            fn ($query) => $query->orderByRaw(
                $this->formatOrderQuery($keys, $keyName),
                $keys
            )
        );
    }

    /**
     * Retrieves the model keys matching
     * the given search query string.
     */
    protected function applySearch(Builder $query, string $search, string $keyName): Collection
    {
        return SearchBuilder::for($query)
            ->withSearchableAttributes($this->getSearchableAttributes())
            ->search($search)
            ->pluck($keyName);
    }

    /**
     * Formats the order by operator to
     * order the query with the given keys.
     */
    protected function formatOrderQuery(Collection $keys, string $keyName): ?string
    {
        return $keys
            ->map(fn ($key, $order) => "WHEN $keyName=? THEN $order")
            ->prepend('CASE')
            ->add('END')
            ->join(' ');
    }
}
