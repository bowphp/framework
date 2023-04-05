<?php

namespace Bow\Database\Migration\Shortcut;

trait DateColumn
{
    /**
     * Add datetime column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addDatetime(string $column, array $attribute = [])
    {
        if ($this->adapter == 'sqlite') {
            return $this->addColumn($column, 'text', $attribute);
        }

        return $this->addColumn($column, 'datetime', $attribute);
    }

    /**
     * Add date column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addDate(string $column, array $attribute = [])
    {
        return $this->addColumn($column, 'date', $attribute);
    }

    /**
     * Add time column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addTime(string $column, array $attribute = [])
    {
        return $this->addColumn($column, 'time', $attribute);
    }

    /**
     * Add year column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addYear(string $column, array $attribute = [])
    {
        return $this->addColumn($column, 'year', $attribute);
    }

    /**
     * Add timestamp column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addTimestamp(string $column, array $attribute = [])
    {
        return $this->addColumn($column, 'timestamp', $attribute);
    }

    /**
     * Add default timestamps
     *
     * @return SQLGenerator
     */
    public function addTimestamps()
    {
        if ($this->adapter == 'sqlite') {
            $this->addColumn('created_at', 'text', ['default' => 'CURRENT_TIMESTAMP']);
            $this->addColumn('updated_at', 'text', ['default' => 'CURRENT_TIMESTAMP']);
        } else {
            $this->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
            $this->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        }

        return $this;
    }

    /**
     * Change datetime column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeDatetime(string $column, array $attribute = [])
    {
        if ($this->adapter == 'sqlite') {
            return $this->changeColumn($column, 'text', $attribute);
        }

        return $this->changeColumn($column, 'datetime', $attribute);
    }

    /**
     * Change date column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeDate(string $column, array $attribute = [])
    {
        return $this->changeColumn($column, 'date', $attribute);
    }

    /**
     * Change time column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeTime(string $column, array $attribute = [])
    {
        return $this->changeColumn($column, 'time', $attribute);
    }

    /**
     * Change year column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeYear(string $column, array $attribute = [])
    {
        return $this->changeColumn($column, 'year', $attribute);
    }

    /**
     * Change timestamp column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeTimestamp(string $column, array $attribute = [])
    {
        return $this->changeColumn($column, 'timestamp', $attribute);
    }

    /**
     * Change default timestamps
     *
     * @return SQLGenerator
     */
    public function changeTimestamps()
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
