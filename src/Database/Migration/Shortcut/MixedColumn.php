<?php

namespace Bow\Database\Migration\Shortcut;

trait MixedColumn
{
    /**
     * Add string column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addString($column, $attribute = [])
    {
        return $this->addColumn($column, 'string', $attribute);
    }

    /**
     * Add float column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addFloat($column, $attribute = [])
    {
        return $this->addColumn($column, 'float', $attribute);
    }

    /**
     * Add double column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addDouble($column, $attribute = [])
    {
        return $this->addColumn($column, 'double', $attribute);
    }
}
