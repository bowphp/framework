<?php

namespace Bow\Database\Migration\Shortcut;

trait MixedColumn
{
    /**
     * Add boolean column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addBoolean($column, array $attribute = [])
    {
        return $this->addColumn($column, 'boolean', $attribute);
    }

    /**
    * Add UUID column
    *
    * @param string $column
    * @param array $attribute
    * @return SQLGenerator
    */
    public function addUuid($column, array $attribute = [])
    {
        return $this->addColumn($column, 'uuid', $attribute);
    }

   /**
    * Add BLOB column
    *
    * @param string $column
    * @param array $attribute
    * @return SQLGenerator
    */
    public function addBinary($column, array $attribute = [])
    {
        return $this->addColumn($column, 'blob', $attribute);
    }

    /**
     * Add ip column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addIpAddress($column, array $attribute = [])
    {
        return $this->addColumn($column, 'ip', $attribute);
    }

    /**
     * Add mac column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addMacAddress($column, array $attribute = [])
    {
        return $this->addColumn($column, 'mac', $attribute);
    }
}
