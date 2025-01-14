<?php

declare(strict_types=1);

namespace Bow\Database\Barry;

use Bow\Database\Collection;
use Bow\Database\QueryBuilder;
use Bow\Database\Exception\ModelException;

class Builder extends QueryBuilder
{
    /**
     * The model instance
     *
     * @var string
     */
    protected ?string $model = null;

    /**
     * Get informations
     *
     * @param array $columns
     * @return Model|Collection
     */
    public function get(array $columns = []): Model|Collection|null
    {
        $data = parent::get($columns);

        if (is_null($data)) {
            return null;
        }

        // Create the model associate to the query builder with query result
        if (!is_array($data)) {
            return new $this->model((array) $data);
        }

        foreach ($data as $key => $value) {
            $data[$key] = new $this->model((array) $value);
        }

        return new Collection($data);
    }

    /**
     * Check if rows exists
     *
     * @param string $column
     * @param mixed $value
     * @return bool
     * @throws
     */
    public function exists(?string $column = null, mixed $value = null): bool
    {
        if (is_null($column) && is_null($value)) {
            return $this->count() > 0;
        }

        // If value is null and column is define
        // we make the column as value on model primary key name
        if (!is_null($column) && is_null($value)) {
            $value = $column;

            $column = (new $this->model())->getKey();
        }

        return $this->whereIn($column, (array) $value)->count() > 0;
    }

    /**
     * Set model
     *
     * @param string $model
     * @return Builder
     */
    public function setModel(string $model): Builder
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get model
     *
     * @return string
     */
    public function getModel(): string
    {
        if (is_null($this->model)) {
            throw new ModelException("The model is not define");
        }

        return (string) $this->model;
    }
}
