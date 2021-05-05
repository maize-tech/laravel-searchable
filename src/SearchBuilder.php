<?php

namespace HFarm\Searchable;

use HFarm\Searchable\Concerns\AddsSearchTermToQuery;
use HFarm\Searchable\Concerns\JoinsRelationshipsToQuery;
use HFarm\Searchable\Utils\Parser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SearchBuilder extends Builder
{
    use AddsSearchTermToQuery;
    use JoinsRelationshipsToQuery;

    /** @var Collection */
    private $searchableAttributes;
    /** @var Collection */
    private $searchWeights;

    /** @var int */
    private $defaultMatchWeight;

    /**
     * SearchBuilder constructor.
     *
     * @param Builder $builder
     */
    public function __construct(Builder $builder)
    {
        parent::__construct($builder->getQuery()->newQuery());

        $this->initializeFromBuilder($builder);

        $this->searchableAttributes = collect();
        $this->searchWeights = collect();

        $this->defaultMatchWeight = config('searchable.default_match_weight', 1);
    }

    /**
     * Creates a new SearchBuilder instance.
     *
     * @param Builder $builder
     * @return static
     */
    public static function for(Builder $builder): self
    {
        return new static($builder);
    }

    /**
     * Adds the given attributes to the searchable attributes list.
     *
     * @param array $attributes
     * @return static
     */
    public function withSearchableAttributes(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (is_numeric($key)) {
                $this->searchableAttributes->push(new SearchableAttribute($value, $this->defaultMatchWeight));
            } else {
                $this->searchableAttributes->push(new SearchableAttribute($key, $value));
            }
        }

        return $this;
    }

    /**
     * Searches through the searchable attributes the given search string.
     *
     * @param string $search
     * @return static
     */
    public function search(string $search): self
    {
        $searchTerms = Parser::parseQuery($search);

        if (empty($searchTerms) || $this->searchableAttributes->isEmpty()) {
            return $this;
        }

        return $this
            ->joinRelationships($this->query)
            ->querySearchTerms($searchTerms)
            ->orderSearchQuery();
    }

    /**
     * Queries all terms within the related attribute.
     *
     * @param array $searchTerms
     * @return static
     */
    protected function querySearchTerms(array $searchTerms): self
    {
        return $this->where(function (Builder $query) use ($searchTerms) {
            foreach ($this->searchableAttributes as $searchableAttribute) {
                foreach ($searchTerms as $searchTerm) {
                    $this->searchTerm(
                        $query,
                        $searchableAttribute->getAttribute(),
                        $searchableAttribute->getWeight(),
                        $searchTerm
                    );
                }
            }
        });
    }

    /**
     * Orders the query results with the sum of all weights
     * of each term matched against a single entry.
     *
     * @return static
     */
    protected function orderSearchQuery(): self
    {
        return $this->orderBy(function ($query) {
            $tableName = $this->getModel()->getTable();
            $tableKey = $this->getModel()->getKeyName();
            $select = $this->searchWeights->pluck('query')->implode('+');
            $bindings = $this->searchWeights->pluck('value')->toArray();

            $this->joinRelationships($query, 'sw');

            $query->selectRaw($select, $bindings)
                ->from($tableName, 'sw')
                ->whereColumn("sw.$tableKey", "$tableName.$tableKey")
                ->limit(1);
        }, 'desc');
    }

    /**
     * Add the model, scopes, eager loaded relationships, local macro's and onDelete callback
     * from the $builder to this query builder.
     *
     * @param Builder $builder
     * @return void
     */
    protected function initializeFromBuilder(Builder $builder): void
    {
        $this
            ->setModel($builder->getModel())
            ->setEagerLoads($builder->getEagerLoads());

        $builder->macro('getProtected', function (Builder $builder, string $property) {
            return $builder->{$property};
        });

        $this->scopes = $builder->getProtected('scopes');

        $this->localMacros = $builder->getProtected('localMacros');

        $this->onDelete = $builder->getProtected('onDelete');
    }
}
