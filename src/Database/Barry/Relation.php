<?php

declare(strict_types=1);

namespace Bow\Database\Barry;

use Closure;
use Bow\Database\Collection;
use Bow\Database\QueryBuilder;

abstract class Relation
{
    /**
     * Indicates whether the relation is adding constraints.
     *
     * @var bool
     */
    protected static bool $has_constraints = true;

    /**
     * Indicate whether the relationships use a pivot table.*.
     *
     * @var bool
     */
    protected static bool $has_pivot = false;

    /**
     * The foreign key of the parent model.
     *
     * @var string
     */
    protected string $foreign_key;

    /**
     * The associated key on the parent model.
     *
     * @var string
     */
    protected string $local_key;

    /**
     * The parent model instance
     *
     * @var Model
     */
    protected Model $parent;

    /**
     * The related model instance
     *
     * @var Model
     */
    protected Model $related;

    /**
     * The Bow Query builder
     *
     * @var QueryBuilder
     */
    protected QueryBuilder $query;

    /**
     * Whether no parent exposed a key during eager loading, in which case the
     * relation resolves to nothing without querying the database.
     *
     * @var bool
     */
    protected bool $eager_has_no_keys = false;

    /**
     * Relation Contractor
     *
     * @param Model $related
     * @param Model $parent
     */
    public function __construct(Model $related, Model $parent)
    {
        $this->parent = $parent;
        $this->related = $related;

        // Clone the model's shared static query builder so the constraints we
        // apply below stay local to this relation. Without the clone, a relation
        // that builds constraints but does not execute the query (e.g. a cache
        // hit in BelongsTo/HasOne) would leave a pending WHERE clause on the
        // shared builder and corrupt the next relation query on the same model.
        $this->query = clone $this->related::query();

        // Build the constraint effect
        if (static::$has_constraints) {
            $this->addConstraints();
        }
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    abstract public function addConstraints(): void;

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    abstract public function getResults(): mixed;

    /**
     * The parent attribute whose value is matched against the related models.
     *
     * @return string
     */
    abstract protected function eagerParentKey(): string;

    /**
     * The related column queried when eager loading the relation.
     *
     * @return string
     */
    abstract protected function eagerRelatedKey(): string;

    /**
     * Whether the relation resolves to many related models.
     *
     * @return bool
     */
    abstract protected function eagerIsMany(): bool;

    /**
     * Get the parent model.
     *
     * @return Model
     */
    public function getParent(): Model
    {
        return $this->parent;
    }

    /**
     * Get associated model class.
     *
     * @return Model
     */
    public function getRelated(): Model
    {
        return $this->related;
    }

    /**
     * Create a new row of the related
     *
     * @param  array $attributes
     * @return Model
     */
    public function create(array $attributes): Model
    {
        $attributes[$this->foreign_key] = $this->parent->getKeyValue();

        return $this->related->create($attributes);
    }

    /**
     * Run the given callback with relation constraints disabled.
     *
     * Lets the eager loader build a relation without the single parent WHERE
     * clause so it can be replaced by a batched whereIn over every parent.
     *
     * @param  Closure $callback
     * @return mixed
     */
    public static function noConstraints(Closure $callback): mixed
    {
        $previous = static::$has_constraints;
        static::$has_constraints = false;

        try {
            return $callback();
        } finally {
            static::$has_constraints = $previous;
        }
    }

    /**
     * Constrain the relation query to every parent key in a single whereIn.
     *
     * @param  Model[] $parents
     * @return void
     */
    public function addEagerConstraints(array $parents): void
    {
        $keys = array_values(array_unique(array_filter(
            array_map(fn (Model $parent) => $parent->getAttribute($this->eagerParentKey()), $parents),
            fn ($value) => !is_null($value)
        )));

        // With no keys to match, skip the value-based constraint entirely.
        // Injecting a placeholder value (such as 0) is rejected by strongly
        // typed columns — e.g. a PostgreSQL uuid primary key raises
        // "invalid input syntax for type uuid: 0". getEager() short-circuits.
        if (count($keys) === 0) {
            $this->eager_has_no_keys = true;

            return;
        }

        $this->query->whereIn($this->eagerRelatedKey(), $keys);
    }

    /**
     * Execute the eager query and return the related models.
     *
     * @return Collection
     */
    public function getEager(): Collection
    {
        // No parent exposed a key, so there is nothing to fetch.
        if ($this->eager_has_no_keys) {
            return new Collection([]);
        }

        $results = $this->query->get();

        return $results instanceof Collection ? $results : new Collection([]);
    }

    /**
     * Match the eager loaded related models back onto their parents.
     *
     * @param  Model[]    $parents
     * @param  Collection $results
     * @param  string     $name
     * @return void
     */
    public function match(array $parents, Collection $results, string $name): void
    {
        $dictionary = [];

        foreach ($results as $related) {
            $dictionary[$related->getAttribute($this->eagerRelatedKey())][] = $related;
        }

        foreach ($parents as $parent) {
            $key = $parent->getAttribute($this->eagerParentKey());
            $matched = $dictionary[$key] ?? [];

            $parent->setRelation(
                $name,
                $this->eagerIsMany() ? new Collection($matched) : ($matched[0] ?? null)
            );
        }
    }

    /**
     * _Call
     *
     * @param  string $method
     * @param  array  $args
     * @return mixed
     */
    public function __call(string $method, array $args = [])
    {
        $result = call_user_func_array([$this->query, $method], (array)$args);

        if ($result === $this->query) {
            return $this;
        }

        return $result;
    }
}
