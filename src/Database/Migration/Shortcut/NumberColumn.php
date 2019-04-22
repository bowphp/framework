<?php

namespace Bow\Database\Migration\Shortcut;

trait NumberColumn
{
    /**
     * Add integer primary column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function addIntegerPrimary($column)
    {
        return $this->addColumn($column, 'int', ['primary' => true]);
    }

    /**
     * Add double primary column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function addDoublePrimary($column)
    {
        return $this->addColumn($column, 'double', ['primary' => true]);
    }

    /**
     * Add float primary column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function addFloatPrimary($column)
    {
        return $this->addColumn($column, 'float', ['primary' => true]);
    }

    /**
     * Add increment primary column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function addIncrement($column)
    {
        return $this->addColumn($column, 'int', ['primary' => true, 'increment' => true]);
    }

    /**
     * Add big increment primary column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function addBigIncrement($column)
    {
        return $this->addColumn($column, 'bigint', ['primary' => true, 'increment' => true]);
    }

    /**
     * Add tiny integer column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addTinyInteger($column, $attribute = [])
    {
        return $this->addColumn($column, 'tinyint', $attribute);
    }

    /**
     * Add integer column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addInteger($column, $attribute = [])
    {
        return $this->addColumn($column, 'int', $attribute);
    }

    /**
     * Add Big integer column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addBigInteger($column, $attribute = [])
    {
        return $this->addColumn($column, 'bigint', $attribute);
    }
}
