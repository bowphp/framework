<?php

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
    protected $parent;

    /**
     * The related model instance
     *
     * @var Model
     */
    protected $related;

    /**
     * The Bow Query builder
     *
     * @var QueryBuilder
     */
    protected $query;

    /**
     * Indicates whether the relation is adding constraints.
     *
     * @var bool
     */
    protected static $has_constraints = true;

    /**
     * Indicate whether the relationships use a pivot table.*.
     *
     * @var bool
     */
    protected static $has_pivot = false;

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

        if (static::$has_constraints) {
            $this->addConstraints();
        }
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    abstract public function addConstraints();

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    abstract public function getResults();

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
    public function getRelated()
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
    public function __call($method, $args)
    {
        $result = call_user_func_array([$this->query, $method], $args);

        if ($result === $this->query) {
            return $this;
        }

        return $result;
    }
}
