<?php

namespace Maize\Searchable\Utils;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause as Join;
use LogicException;

class Joiner
{
    /**
     * Processed query instance
     *
     * @var Builder
     */
    protected $query;

    /**
     * Parent model
     *
     * @var Model
     */
    protected $model;

    /**
     * Parent table name alias
     *
     * @var string|null
     */
    protected $tableName;

    /**
     * Create new joiner instance.
     */
    public function __construct(Builder $query, Model $model, ?string $as)
    {
        $this->query = $query;
        $this->model = $model;
        $this->tableName = $as ?? $model->getTable();
    }

    /**
     * Join related tables.
     */
    public static function joinAll(Builder $query, Model $model, array $targets, ?string $as = null, string $type = 'inner'): void
    {
        $joiner = new self($query, $model, $as);

        foreach ($targets as $target) {
            $joiner->join($target, $type);
        }
    }

    /**
     * Left join related tables.
     */
    public static function leftJoinAll(Builder $query, Model $model, array $targets, ?string $as = null): void
    {
        $joiner = new self($query, $model, $as);

        foreach ($targets as $target) {
            $joiner->leftJoin($target);
        }
    }

    /**
     * Left join related tables.
     */
    public static function rightJoinAll(Builder $query, Model $model, array $targets, ?string $as = null): void
    {
        $joiner = new self($query, $model, $as);

        foreach ($targets as $target) {
            $joiner->rightJoin($target);
        }
    }

    /**
     * Join related tables.
     */
    public function join(string $target, string $type = 'inner'): Model
    {
        $related = $this->model;

        foreach (explode('.', $target) as $segment) {
            $related = $this->joinSegment($related, $segment, $type);
        }

        return $related;
    }

    /**
     * Left join related tables.
     */
    public function leftJoin(string $target): Model
    {
        return $this->join($target, 'left');
    }

    /**
     * Right join related tables.
     */
    public function rightJoin(string $target): Model
    {
        return $this->join($target, 'right');
    }

    /**
     * Join relation's table accordingly.
     */
    protected function joinSegment(Model $parent, string $segment, string $type): Model
    {
        $relation = $parent->{$segment}();
        $related = $relation->getRelated();
        $table = $related->getTable();

        if ($relation instanceof BelongsToMany || $relation instanceof HasManyThrough) {
            $this->joinIntermediate($parent, $relation, $type);
        }

        if (! $this->alreadyJoined($join = $this->getJoinClause($parent, $relation, $table, $type))) {
            $this->query->joins[] = $join;
        }

        return $related;
    }

    /**
     * Determine whether the related table has been already joined.
     */
    protected function alreadyJoined(Join $join): bool
    {
        return in_array($join, (array) $this->query->joins);
    }

    /**
     * Get the join clause for related table.
     */
    protected function getJoinClause(Model $parent, Relation $relation, string $table, string $type): Join
    {
        [$fk, $pk] = $this->getJoinKeys($relation);

        $join = (new Join($parent::query()->getQuery(), $type, $table))->on($fk, '=', $pk);

        $related = $relation->getRelated();
        if (is_a($related, SoftDeletes::class)) {
            $join->whereNull($related->getQualifiedDeletedAtColumn());
        }

        if ($relation instanceof MorphOneOrMany) {
            $join->where($relation->getQualifiedMorphType(), '=', $parent->getMorphClass());
        } elseif ($relation instanceof MorphToMany) {
            $join->where($relation->getMorphType(), '=', $parent->getMorphClass());
        }

        return $join;
    }

    /**
     * Join pivot or 'through' table.
     */
    protected function joinIntermediate(Model $parent, BelongsToMany|HasManyThrough $relation, string $type): void
    {
        $table = match (true) {
            $relation instanceof BelongsToMany => $relation->getTable(),
            $relation instanceof HasManyThrough => $relation->getParent()->getTable(),
        };

        $fk = match (true) {
            $relation instanceof BelongsToMany => $relation->getQualifiedForeignPivotKeyName(),
            $relation instanceof HasManyThrough => $relation->getQualifiedFirstKeyName(),
        };

        $pk = "{$this->tableName}.{$parent->getKeyName()}";

        if (! $this->alreadyJoined($join = (new Join($this->query, $type, $table))->on($fk, '=', $pk))) {
            $this->query->joins[] = $join;
        }
    }

    /**
     * Get pair of the keys from relation in order to join the table.
     *
     * @throws LogicException
     */
    protected function getJoinKeys(Relation $relation): array
    {
        if ($relation instanceof MorphTo) {
            throw new LogicException('MorphTo relation cannot be joined.');
        }

        $isSelfParent = $relation->getParent()->getMorphClass() === $this->model->getMorphClass();

        if ($relation instanceof HasOneOrMany) {
            return $isSelfParent
                ? [$relation->getQualifiedForeignKeyName(), "{$this->tableName}.{$relation->getLocalKeyName()}"]
                : [$relation->getQualifiedForeignKeyName(), $relation->getQualifiedParentKeyName()];
        }

        if ($relation instanceof BelongsTo) {
            return $isSelfParent
                ? ["{$this->tableName}.{$relation->getForeignKeyName()}", $relation->getQualifiedOwnerKeyName()]
                : [$relation->getQualifiedForeignKeyName(), $relation->getQualifiedOwnerKeyName()];
        }

        if ($relation instanceof BelongsToMany) {
            return [$relation->getQualifiedRelatedPivotKeyName(), $relation->getRelated()->getQualifiedKeyName()];
        }

        if ($relation instanceof HasManyThrough) {
            return [$relation->getQualifiedFarKeyName(), $relation->getQualifiedParentKeyName()];
        }

        throw new LogicException('Unknown relation type.');
    }
}
