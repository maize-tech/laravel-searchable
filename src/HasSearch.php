<?php

namespace HFarm\Searchable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait HasSearch
{
    /**
     * Get the model's searchable attributes.
     *
     * @return array
     */
    abstract public function getSearchableAttributes(): array;

    /**
     * Retrieves the models matching the given query string.
     * If orderByWeight is set to true, orders the list by the
     * weight of each matching model attributes.
     *
     * @param Builder $query
     * @param string $search
     * @param bool $orderByWeight
     */
    public function scopeSearch(Builder $query, string $search, bool $orderByWeight = true): void
    {
        $keyName = $query->getModel()->getQualifiedKeyName();
        $keys = $this->applySearch($query, $search, $keyName);

        $query
            ->whereIn($keyName, $keys)
            ->when($keys->isNotEmpty() && $orderByWeight, function ($query) use ($keyName, $keys) {
                $query->orderByRaw(
                    $this->formatOrderQuery($keys, $keyName),
                    $keys
                );
            });
    }

    /**
     * Retrieves the model keys matching
     * the given search query string.
     *
     * @param Builder $query
     * @param string $search
     * @param string $keyName
     * @return Collection
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
     *
     * @param Collection $keys
     * @param string $keyName
     * @return string|null
     */
    protected function formatOrderQuery(Collection $keys, string $keyName): ?string
    {
        return $keys
            ->map(fn ($key, $order) => "WHEN $keyName=? THEN $order")
            ->prepend("CASE")
            ->add("END")
            ->join(' ');
    }
}
