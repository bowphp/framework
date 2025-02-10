<?php

declare(strict_types=1);

namespace Bow\Database\Migration\Shortcut;

use Bow\Database\Exception\SQLGeneratorException;
use Bow\Database\Migration\Table;

trait NumberColumn
{
    /**
     * Add float column
     *
     * @param string $column
     * @param array $attribute
     * @return Table
     * @throws SQLGeneratorException
     */
    public function addFloat(string $column, array $attribute = []): Table
    {
        return $this->addColumn($column, 'float', $attribute);
    }

    /**
     * Add double column
     *
     * @param string $column
     * @param array $attribute
     * @return Table
     * @throws SQLGeneratorException
     */
    public function addDouble(string $column, array $attribute = []): Table
    {
        return $this->addColumn($column, 'double', $attribute);
    }

    /**
     * Add double primary column
     *
     * @param string $column
     * @return Table
     * @throws SQLGeneratorException
     */
    public function addDoublePrimary(string $column): Table
    {
        return $this->addColumn($column, 'double', ['primary' => true]);
    }

    /**
     * Add float primary column
     *
     * @param string $column
     * @return Table
     * @throws SQLGeneratorException
     */
    public function addFloatPrimary(string $column): Table
    {
        return $this->addColumn($column, 'float', ['primary' => true]);
    }

    /**
     * Add increment primary column
     *
     * @param string $column
     * @return Table
     * @throws SQLGeneratorException
     */
    public function addIncrement(string $column): Table
    {
        return $this->addColumn($column, 'int', ['primary' => true, 'increment' => true]);
    }

    /**
     * Add integer column
     *
     * @param string $column
     * @param array $attribute
     * @return Table
     * @throws SQLGeneratorException
     */
    public function addInteger(string $column, array $attribute = []): Table
    {
        return $this->addColumn($column, 'int', $attribute);
    }

    /**
     * Add integer primary column
     *
     * @param string $column
     * @return Table
     * @throws SQLGeneratorException
     */
    public function addIntegerPrimary(string $column): Table
    {
        return $this->addColumn($column, 'int', ['primary' => true]);
    }

    /**
     * Add big increment primary column
     *
     * @param string $column
     * @return Table
     * @throws SQLGeneratorException
     */
    public function addBigIncrement(string $column): Table
    {
        return $this->addColumn($column, 'bigint', ['primary' => true, 'increment' => true]);
    }

    /**
     * Add tiny integer column
     *
     * @param string $column
     * @param array $attribute
     * @return Table
     * @throws SQLGeneratorException
     */
    public function addTinyInteger(string $column, array $attribute = []): Table
    {
        return $this->addColumn($column, 'tinyint', $attribute);
    }

    /**
     * Add Big integer column
     *
     * @param string $column
     * @param array $attribute
     * @return Table
     * @throws SQLGeneratorException
     */
    public function addBigInteger(string $column, array $attribute = []): Table
    {
        return $this->addColumn($column, 'bigint', $attribute);
    }

    /**
     * Add Medium integer column
     *
     * @param string $column
     * @param array $attribute
     * @return Table
     * @throws SQLGeneratorException
     */
    public function addMediumInteger(string $column, array $attribute = []): Table
    {
        return $this->addColumn($column, 'mediumint', $attribute);
    }

    /**
     * Add Medium integer column
     *
     * @param string $column
     * @return Table
     * @throws SQLGeneratorException
     */
    public function addMediumIncrement(string $column): Table
    {
        return $this->addColumn($column, 'mediumint', ['primary' => true, 'increment' => true]);
    }

    /**
     * Add small integer column
     *
     * @param string $column
     * @param array $attribute
     * @return Table
     * @throws SQLGeneratorException
     */
    public function addSmallInteger(string $column, array $attribute = []): Table
    {
        return $this->addColumn($column, 'smallint', $attribute);
    }

    /**
     * Add Smallint integer column
     *
     * @param string $column
     * @return Table
     * @throws SQLGeneratorException
     */
    public function addSmallIntegerIncrement(string $column): Table
    {
        return $this->addColumn($column, 'smallint', ['primary' => true, 'increment' => true]);
    }

    /**
     * Change float column
     *
     * @param string $column
     * @param array $attribute
     * @return Table
     * @throws SQLGeneratorException
     */
    public function changeFloat(string $column, array $attribute = []): Table
    {
        return $this->changeColumn($column, 'float', $attribute);
    }

    /**
     * Change double column
     *
     * @param string $column
     * @param array $attribute
     * @return Table
     * @throws SQLGeneratorException
     */
    public function changeDouble(string $column, array $attribute = []): Table
    {
        return $this->changeColumn($column, 'double', $attribute);
    }

    /**
     * Change double primary column
     *
     * @param string $column
     * @return Table
     * @throws SQLGeneratorException
     */
    public function changeDoublePrimary(string $column): Table
    {
        return $this->changeColumn($column, 'double', ['primary' => true]);
    }

    /**
     * Change float primary column
     *
     * @param string $column
     * @return Table
     * @throws SQLGeneratorException
     */
    public function changeFloatPrimary(string $column): Table
    {
        return $this->changeColumn($column, 'float', ['primary' => true]);
    }

    /**
     * Change increment primary column
     *
     * @param string $column
     * @return Table
     * @throws SQLGeneratorException
     */
    public function changeIncrement(string $column): Table
    {
        return $this->changeColumn($column, 'int', ['primary' => true, 'increment' => true]);
    }

    /**
     * Change integer column
     *
     * @param string $column
     * @param array $attribute
     * @return Table
     * @throws SQLGeneratorException
     */
    public function changeInteger(string $column, array $attribute = []): Table
    {
        return $this->changeColumn($column, 'int', $attribute);
    }

    /**
     * Change integer primary column
     *
     * @param string $column
     * @return Table
     * @throws SQLGeneratorException
     */
    public function changeIntegerPrimary(string $column): Table
    {
        return $this->changeColumn($column, 'int', ['primary' => true]);
    }

    /**
     * Change big increment primary column
     *
     * @param string $column
     * @return Table
     * @throws SQLGeneratorException
     */
    public function changeBigIncrement(string $column): Table
    {
        return $this->changeColumn($column, 'bigint', ['primary' => true, 'increment' => true]);
    }

    /**
     * Change tiny integer column
     *
     * @param string $column
     * @param array $attribute
     * @return Table
     * @throws SQLGeneratorException
     */
    public function changeTinyInteger(string $column, array $attribute = []): Table
    {
        return $this->changeColumn($column, 'tinyint', $attribute);
    }

    /**
     * Change Big integer column
     *
     * @param string $column
     * @param array $attribute
     * @return Table
     * @throws SQLGeneratorException
     */
    public function changeBigInteger(string $column, array $attribute = []): Table
    {
        return $this->changeColumn($column, 'bigint', $attribute);
    }

    /**
     * Change Medium integer column
     *
     * @param string $column
     * @param array $attribute
     * @return Table
     * @throws SQLGeneratorException
     */
    public function changeMediumInteger(string $column, array $attribute = []): Table
    {
        return $this->changeColumn($column, 'mediumint', $attribute);
    }

    /**
     * Change Medium integer column
     *
     * @param string $column
     * @return Table
     * @throws SQLGeneratorException
     */
    public function changeMediumIncrement(string $column): Table
    {
        return $this->changeColumn($column, 'mediumint', ['primary' => true, 'increment' => true]);
    }

    /**
     * Change Small integer column
     *
     * @param string $column
     * @param array $attribute
     * @return Table
     * @throws SQLGeneratorException
     */
    public function changeSmallInteger(string $column, array $attribute = []): Table
    {
        return $this->changeColumn($column, 'smallint', $attribute);
    }

    /**
     * Change Small integer column
     *
     * @param string $column
     * @return Table
     * @throws SQLGeneratorException
     */
    public function changeSmallIntegerPrimary(string $column): Table
    {
        return $this->changeColumn($column, 'smallint', ['primary' => true, 'increment' => true]);
    }
}
