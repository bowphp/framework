<?php

namespace Bow\Database\Migration\Shortcut;

use Bow\Database\Migration\SQLGenerator;

trait MixedColumn
{
    /**
     * Add boolean column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addBoolean(string $column, array $attribute = []): SQLGenerator
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
    public function addUuid(string $column, array $attribute = []): SQLGenerator
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
    public function addBinary(string $column, array $attribute = []): SQLGenerator
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
    public function addIpAddress(string $column, array $attribute = []): SQLGenerator
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
    public function addMacAddress(string $column, array $attribute = []): SQLGenerator
    {
        return $this->addColumn($column, 'mac', $attribute);
    }
}
