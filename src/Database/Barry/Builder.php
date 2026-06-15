<?php

declare(strict_types=1);

namespace Bow\Database\Barry;

use Bow\Database\Collection;
use Bow\Database\Exception\ModelException;
use Bow\Database\Exception\QueryBuilderException;
use Bow\Database\QueryBuilder;

class Builder extends QueryBuilder
{
    /**
     * The model instance
     *
     * @var ?string
     */
    protected ?string $model = null;

    /**
     * The relationships to eager load.
     *
     * @var array
     */
    protected array $eager_loads = [];

    /**
     * Register relationships to eager load on the query result.
     *
     * @param  string|array $relations
     * @return Builder
     */
    public function eager(string|array $relations): Builder
    {
        $this->eager_loads = array_merge($this->eager_loads, (array)$relations);

        return $this;
    }

    /**
     * Get information
     *
     * @param  array $columns
     * @return Model|Collection|null
     */
    public function get(array $columns = []): Model|Collection|null
    {
        $data = parent::get($columns);

        // Read and reset the eager loads now: query() memoizes a shared Builder
        // instance, so the list must not leak into the next query on this model.
        $eager_loads = $this->eager_loads;
        $this->eager_loads = [];

        if (is_null($data)) {
            return null;
        }

        // Create the model associate to the query builder with query result
        if (!is_array($data)) {
            return new $this->model((array)$data);
        }

        foreach ($data as $key => $value) {
            $data[$key] = new $this->model((array)$value);
        }

        if (count($eager_loads) > 0) {
            $this->eagerLoadRelations($data, $eager_loads);
        }

        return new Collection($data);
    }

    /**
     * Eager load the given relationships onto a set of parent models.
     *
     * @param  Model[] $models
     * @param  array   $relations
     * @return void
     */
    protected function eagerLoadRelations(array $models, array $relations): void
    {
        if (count($models) === 0) {
            return;
        }

        foreach ($relations as $name) {
            // Build the relation without the single parent constraint so it can
            // be batched across every parent with one whereIn query.
            $relation = Relation::noConstraints(fn () => $models[0]->$name());

            $relation->addEagerConstraints($models);
            $relation->match($models, $relation->getEager(), $name);
        }
    }

    /**
     * Check if rows exists
     *
     * @param  string|null $column
     * @param  mixed       $value
     * @return bool
     * @throws QueryBuilderException
     */
    public function exists(?string $column = null, mixed $value = null): bool
    {
        if (is_null($column) && is_null($value)) {
            return $this->count() > 0;
        }

        // If value is null and column is defined
        // we make the column as value on model primary key name
        if (!is_null($column) && is_null($value)) {
            $value = $column;

            $column = (new $this->model())->getKey();
        }

        return $this->whereIn($column, (array)$value)->count() > 0;
    }

    /**
     * Get model
     *
     * @return string
     * @throws ModelException
     */
    public function getModel(): string
    {
        if (is_null($this->model)) {
            throw new ModelException("The model is not define");
        }

        return (string)$this->model;
    }

    /**
     * Set model
     *
     * @param  string $model
     * @return Builder
     */
    public function setModel(string $model): Builder
    {
        $this->model = $model;

        return $this;
    }
}
