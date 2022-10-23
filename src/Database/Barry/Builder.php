<?php declare(strict_types=1);

namespace Bow\Database\Barry;

use Bow\Database\Collection;
use Bow\Database\QueryBuilder;

class Builder extends QueryBuilder
{
    /**
     * The model instance
     *
     * @var string
     */
    protected $model;

    /**
     * Get informations
     *
     * @param array $columns
     * @return mixed
     */
    public function get(array $columns = [])
    {
        $data = parent::get($columns);

        $model = $this->model;

        if (!is_array($data)) {
            if (is_null($data)) {
                return null;
            }

            return new $model((array) $data);
        }
        
        foreach ($data as $key => $value) {
            $data[$key] = new $model((array) $value);
        }

        return new Collection($data);
    }

    /**
     * Check if rows exists
     *
     * @param string $column
     * @param string|int $value
     * @return bool
     * @throws
     */
    public function exists(string $column = null, string $value = null): bool
    {
        if ($value == null && $value == null) {
            return $this->count() > 0;
        }

        if ($value == null) {
            $value = $column;

            $column = (new $this->model)->getKey();
        }

        return $this->where($column, $value)->count() > 0;
    }

    /**
     * Set model
     *
     * @param string $model
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
        return $this->model;
    }
}
