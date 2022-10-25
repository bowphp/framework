<?php

namespace Bow\Database\Migration\Shortcut;

use Bow\Database\Migration\SQLGenerator;

trait NumberColumn
{
    /**
     * Add float column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addFloat(string $column, array $attribute = []): SQLGenerator
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
    public function addDouble(string $column, array $attribute = []): SQLGenerator
    {
        return $this->addColumn($column, 'double', $attribute);
    }

    /**
     * Add double primary column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function addDoublePrimary(string $column): SQLGenerator
    {
        return $this->addColumn($column, 'double', ['primary' => true]);
    }

    /**
     * Add float primary column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function addFloatPrimary(string $column): SQLGenerator
    {
        return $this->addColumn($column, 'float', ['primary' => true]);
    }

    /**
     * Add increment primary column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function addIncrement(string $column): SQLGenerator
    {
        return $this->addColumn($column, 'int', ['primary' => true, 'increment' => true]);
    }

    /**
     * Add integer column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addInteger(string $column, array $attribute = []): SQLGenerator
    {
        return $this->addColumn($column, 'int', $attribute);
    }

    /**
     * Add integer primary column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function addIntegerPrimary(string $column): SQLGenerator
    {
        return $this->addColumn($column, 'int', ['primary' => true]);
    }

    /**
     * Add big increment primary column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function addBigIncrement(string $column): SQLGenerator
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
    public function addTinyInteger(string $column, array $attribute = []): SQLGenerator
    {
        return $this->addColumn($column, 'tinyint', $attribute);
    }

    /**
     * Add Big integer column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addBigInteger(string $column, array $attribute = []): SQLGenerator
    {
        return $this->addColumn($column, 'bigint', $attribute);
    }

    /**
     * Add Medium integer column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addMediumInteger(string $column, array $attribute = []): SQLGenerator
    {
        return $this->addColumn($column, 'mediumint', $attribute);
    }

    /**
     * Add Medium integer column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function addMediumIncrement(string $column): SQLGenerator
    {
        return $this->addColumn($column, 'mediumint', ['primary' => true, 'increment' => true]);
    }
}
