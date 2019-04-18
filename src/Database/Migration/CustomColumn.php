<?php

namespace Bow\Database\Migration;

trait CustomColumn
{
    /**
     * Add integer primary column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function addIntegerPrimary($column)
    {
        $this->addColumn($column, 'int', ['primary' => true]);
    }

    /**
     * Add double primary column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function addDoublePrimary($column)
    {
        $this->addColumn($column, 'double', ['primary' => true]);
    }

    /**
     * Add float primary column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function addFloatPrimary($column)
    {
        $this->addColumn($column, 'float', ['primary' => true]);
    }

    /**
     * Add increment primary column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function addIncrement($column)
    {
        $this->addColumn($column, 'float', ['primary' => true, 'increment' => true]);
    }
}
