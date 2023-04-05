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

    /**
     * Add text column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addText($column, array $attribute = [])
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
    public function addBlob($column, array $attribute = [])
    {
        return $this->addColumn($column, 'blob', $attribute);
    }

    /**
     * Change string column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeString($column, array $attribute = [])
    {
        return $this->changeColumn($column, 'string', $attribute);
    }

    /**
     * Change string column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeLongString($column, array $attribute = [])
    {
        return $this->changeColumn($column, 'long varchar', $attribute);
    }

    /**
     * Change json column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeJson($column, array $attribute = [])
    {
        return $this->changeColumn($column, 'json', $attribute);
    }

    /**
     * Change character column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeChar($column, array $attribute = [])
    {
        return $this->changeColumn($column, 'character', $attribute);
    }

    /**
     * Change longtext column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeLongtext($column, array $attribute = [])
    {
        return $this->changeColumn($column, 'longtext', $attribute);
    }

    /**
     * Change text column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeText($column, array $attribute = [])
    {
        return $this->changeColumn($column, 'text', $attribute);
    }

    /**
     * Change blob column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeBlob($column, array $attribute = [])
    {
        return $this->changeColumn($column, 'blob', $attribute);
    }
}
