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

    /**
     * Add enum column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addEnum($column, array $attribute = [])
    {
        return $this->addColumn($column, 'enum', $attribute);
    }

    /**
     * Change boolean column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeBoolean($column, array $attribute = [])
    {
        return $this->changeColumn($column, 'boolean', $attribute);
    }

    /**
    * Change UUID column
    *
    * @param string $column
    * @param array $attribute
    * @return SQLGenerator
    */
    public function changeUuid($column, array $attribute = [])
    {
        return $this->changeColumn($column, 'uuid', $attribute);
    }

   /**
    * Change BLOB column
    *
    * @param string $column
    * @param array $attribute
    * @return SQLGenerator
    */
    public function changeBinary($column, array $attribute = [])
    {
        return $this->changeColumn($column, 'blob', $attribute);
    }

    /**
     * Change ip column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeIpAddress($column, array $attribute = [])
    {
        return $this->changeColumn($column, 'ip', $attribute);
    }

    /**
     * Change mac column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeMacAddress($column, array $attribute = [])
    {
        return $this->changeColumn($column, 'mac', $attribute);
    }

    /**
     * Change enum column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeEnum($column, array $attribute = [])
    {
        return $this->changeColumn($column, 'enum', $attribute);
    }
}
