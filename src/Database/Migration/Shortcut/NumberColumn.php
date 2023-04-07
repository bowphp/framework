<?php

declare(strict_types=1);

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

    /**
     * Change float column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeFloat(string $column, array $attribute = []): SQLGenerator
    {
        return $this->changeColumn($column, 'float', $attribute);
    }

    /**
     * Change double column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeDouble(string $column, array $attribute = []): SQLGenerator
    {
        return $this->changeColumn($column, 'double', $attribute);
    }

    /**
     * Change double primary column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function changeDoublePrimary($column)
    {
        return $this->changeColumn($column, 'double', ['primary' => true]);
    }

    /**
     * Change float primary column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function changeFloatPrimary($column)
    {
        return $this->changeColumn($column, 'float', ['primary' => true]);
    }

    /**
     * Change increment primary column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function changeIncrement(string $column)
    {
        return $this->changeColumn($column, 'int', ['primary' => true, 'increment' => true]);
    }

    /**
     * Change integer column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeInteger(string $column, array $attribute = []): SQLGenerator
    {
        return $this->changeColumn($column, 'int', $attribute);
    }

    /**
     * Change integer primary column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function changeIntegerPrimary(string $column)
    {
        return $this->changeColumn($column, 'int', ['primary' => true]);
    }

    /**
     * Change big increment primary column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function changeBigIncrement(string $column)
    {
        return $this->changeColumn($column, 'bigint', ['primary' => true, 'increment' => true]);
    }

    /**
     * Change tiny integer column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeTinyInteger(string $column, array $attribute = []): SQLGenerator
    {
        return $this->changeColumn($column, 'tinyint', $attribute);
    }

    /**
     * Change Big integer column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeBigInteger(string $column, array $attribute = []): SQLGenerator
    {
        return $this->changeColumn($column, 'bigint', $attribute);
    }

    /**
     * Change Medium integer column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeMediumInteger(string $column, array $attribute = []): SQLGenerator
    {
        return $this->changeColumn($column, 'mediumint', $attribute);
    }

    /**
     * Change Medium integer column
     *
     * @param string $column
     * @return SQLGenerator
     */
    public function changeMediumIncrement(string $column)
    {
        return $this->changeColumn($column, 'mediumint', ['primary' => true, 'increment' => true]);
    }
}
