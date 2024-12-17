<?php

declare(strict_types=1);

namespace Bow\Database\Barry;

use Bow\Database\Barry\Model;
use Bow\Database\QueryBuilder;

abstract class Relation
{
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
     * Relation Contructor
     *
     * @param Model $related
     * @param Model $parent
     */
    public function __construct(Model $related, Model $parent)
    {
        $this->parent = $parent;
        $this->related = $related;
        $this->query = $this->related::query();

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
     * _Call
     *
     * @param string $method
     * @param string $args
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        $result = call_user_func_array([$this->query, $method], (array) $args);

        if ($result === $this->query) {
            return $this;
        }

        return $result;
    }
}
