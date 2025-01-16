<?php

declare(strict_types=1);

namespace Bow\Database\Migration\Shortcut;

use Bow\Database\Exception\SQLGeneratorException;
use Bow\Database\Migration\SQLGenerator;

trait DateColumn
{
    /**
     * Add datetime column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     * @throws SQLGeneratorException
     */
    public function addDatetime(string $column, array $attribute = []): SQLGenerator
    {
        if ($this->adapter == 'pgsql') {
            return $this->addTimestamp($column, $attribute);
        }

        return $this->addColumn($column, 'datetime', $attribute);
    }

    /**
     * Add date column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     * @throws SQLGeneratorException
     */
    public function addDate(string $column, array $attribute = []): SQLGenerator
    {
        return $this->addColumn($column, 'date', $attribute);
    }

    /**
     * Add time column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     * @throws SQLGeneratorException
     */
    public function addTime(string $column, array $attribute = []): SQLGenerator
    {
        return $this->addColumn($column, 'time', $attribute);
    }

    /**
     * Add year column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     * @throws SQLGeneratorException
     */
    public function addYear(string $column, array $attribute = []): SQLGenerator
    {
        return $this->addColumn($column, 'year', $attribute);
    }

    /**
     * Add timestamp column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     * @throws SQLGeneratorException
     */
    public function addTimestamp(string $column, array $attribute = []): SQLGenerator
    {
        return $this->addColumn($column, 'timestamp', $attribute);
    }

    /**
     * Add default timestamps
     *
     * @return SQLGenerator
     * @throws SQLGeneratorException
     */
    public function addTimestamps(): SQLGenerator
    {
        if ($this->adapter == 'pgsql') {
            $this->addTimestamp('created_at', ['default' => 'CURRENT_TIMESTAMP']);
            $this->addTimestamp('updated_at', ['default' => 'CURRENT_TIMESTAMP']);

            return $this;
        }

        $this->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $this->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);

        return $this;
    }

    /**
     * Change datetime column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     * @throws SQLGeneratorException
     */
    public function changeDatetime(string $column, array $attribute = []): SQLGenerator
    {
        if ($this->adapter == 'pgsql') {
            return $this->addTimestamp($column, $attribute);
        }

        return $this->changeColumn($column, 'datetime', $attribute);
    }

    /**
     * Change date column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     * @throws SQLGeneratorException
     */
    public function changeDate(string $column, array $attribute = []): SQLGenerator
    {
        return $this->changeColumn($column, 'date', $attribute);
    }

    /**
     * Change time column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     * @throws SQLGeneratorException
     */
    public function changeTime(string $column, array $attribute = []): SQLGenerator
    {
        return $this->changeColumn($column, 'time', $attribute);
    }

    /**
     * Change year column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     * @throws SQLGeneratorException
     */
    public function changeYear(string $column, array $attribute = []): SQLGenerator
    {
        return $this->changeColumn($column, 'year', $attribute);
    }

    /**
     * Change timestamp column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     * @throws SQLGeneratorException
     */
    public function changeTimestamp(string $column, array $attribute = []): SQLGenerator
    {
        return $this->changeColumn($column, 'timestamp', $attribute);
    }

    /**
     * Change default timestamps
     *
     * @return SQLGenerator
     * @throws SQLGeneratorException
     */
    public function changeTimestamps(): SQLGenerator
    {
        if ($this->adapter == 'sqlite') {
            $this->changeColumn('created_at', 'text', ['default' => 'CURRENT_TIMESTAMP']);
            $this->changeColumn('updated_at', 'text', ['default' => 'CURRENT_TIMESTAMP']);
        } else {
            $this->changeColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
            $this->changeColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        }

        return $this;
    }
}
