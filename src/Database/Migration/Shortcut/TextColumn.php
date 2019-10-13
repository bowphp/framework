<?php

namespace Bow\Database\Migration\Shortcut;

trait TextColumn
{
    /**
     * Add string column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addString($column, array $attribute = [])
    {
        return $this->addColumn($column, 'string', $attribute);
    }

    /**
     * Add string column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addLongString($column, array $attribute = [])
    {
        return $this->addColumn($column, 'long varchar', $attribute);
    }

    /**
     * Add json column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addJson($column, array $attribute = [])
    {
        return $this->addColumn($column, 'json', $attribute);
    }

    /**
     * Add character column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addChar($column, array $attribute = [])
    {
        return $this->addColumn($column, 'character', $attribute);
    }

    /**
     * Add longtext column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addLongtext($column, array $attribute = [])
    {
        return $this->addColumn($column, 'longtext', $attribute);
    }
}
