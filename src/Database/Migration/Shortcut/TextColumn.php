<?php

declare(strict_types=1);

namespace Bow\Database\Migration\Shortcut;

use Bow\Database\Exception\SQLGeneratorException;
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
        return $this->addColumn($column, 'char', $attribute);
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

    /**
     * Change string column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeString(string $column, array $attribute = []): SQLGenerator
    {
        return $this->changeColumn($column, 'string', $attribute);
    }

    /**
     * Change json column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeJson(string $column, array $attribute = []): SQLGenerator
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
    public function changeChar(string $column, array $attribute = []): SQLGenerator
    {
        return $this->changeColumn($column, 'char', $attribute);
    }

    /**
     * Change longtext column
     *
     * @param string $column
     * @param array $attribute
     * @return SQLGenerator
     */
    public function changeLongtext(string $column, array $attribute = []): SQLGenerator
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
    public function changeText(string $column, array $attribute = []): SQLGenerator
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
    public function changeBlob(string $column, array $attribute = []): SQLGenerator
    {
        return $this->changeColumn($column, 'blob', $attribute);
    }
}
