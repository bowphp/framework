<?php

namespace Bow\Database\Barry;

use Bow\Database\Collection;
use Bow\Database\Query\Builder as QueryBuilder;

class Builder extends QueryBuilder
{
    /**
     * @var string
     */
    protected $model;

    /**
     * @inherits
     */
    public function get($columns = [])
    {
        $data = parent::get($columns);

        $model = $this->model;

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = new $model((array) $value);
            }

            return new Collection($data);
        }

        return new $model((array) $data);
    }

    /**
     * @inherits
     */
    public function exists($column = null, $value = null)
    {
        if ($value == null && $value == null) {
            return $this->count() > 0;
        }

        if ($value == null) {
            $value = $column;

            $column = (new $model)->getKey();
        }

        return $this->where($column, $value)->count() > 0;
    }

    /**
     * Set model
     *
     * @param string $model
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * Get model
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }
}
