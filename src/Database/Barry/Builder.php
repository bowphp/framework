<?php

namespace Bow\Database\Barry;

use Bow\Database\Collection;
use Bow\Database\Query\Builder as QueryBuilder;

class Builder extends QueryBuilder
{
    /**
     * @inherits
     */
    public function get($columns = [])
    {
        $data = parent::get($columns);

        if ($this->loadClassName) {
            $loadClassName = $this->loadClassName;
        } else {
            $loadClassName = static::class;
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = new $loadClassName((array) $value);
            }

            return new Collection($data);
        }

        return new $loadClassName((array) $data);
    }
}
