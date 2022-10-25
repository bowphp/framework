<?php

namespace Bow\Database\Migration\Shortcut;

use Bow\Database\Migration\SQLGenerator;

trait TextColumn
{
    /**
     * Add string column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addString(string $column, array $attribute = []): SQLGenerator
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
    public function addLongString(string $column, array $attribute = []): SQLGenerator
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
    public function addJson(string $column, array $attribute = []): SQLGenerator
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
    public function addChar(string $column, array $attribute = []): SQLGenerator
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
    public function addLongtext(string $column, array $attribute = []): SQLGenerator
    {
        return $this->addColumn($column, 'longtext', $attribute);
    }

    /**
     * Add text column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addText(string $column, array $attribute = []): SQLGenerator
    {
        return $this->addColumn($column, 'text', $attribute);
    }

    /**
     * Add blob column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addBlob(string $column, array $attribute = []): SQLGenerator
    {
        return $this->addColumn($column, 'blob', $attribute);
    }
}
