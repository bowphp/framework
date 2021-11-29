<?php

namespace Bow\Database\Migration\Shortcut;

trait ConstraintColumn
{
    /**
     * Add Foreign KEY constraints
     *
     * @param string $name
     * @param array $attributes
     * @return SQLGenerator
     */
    public function addForeign($name, array $attributes = [])
    {
        if ($this->scope == 'alter') {
            $command = 'ADD CONSTRAINT';
        } else {
            $command = 'CONSTRAINT';
        }

        $on = '';
        $references = '';
        $target = sprintf("%s_%s_foreign", $this->getTable(), $name);

        if (isset($attributes['on'])) {
            $on = strtoupper(' ON ' .$attributes['on']);
        }

        if (isset($attributes['references'], $attributes['table'])) {
            $references = sprintf(
                ' REFERENCES %s(%s)',
                $attributes['table'],
                $attributes['references']
            );
        }

        $sql = sprintf(
            '%s %s FOREIGN KEY (`%s`)%s%s',
            $command,
            $target,
            $name,
            $references,
            $on
        );

        $this->sqls[] = $sql;

        return $this;
    }

    /**
     * Drop constraintes column;
     *
     * @param string $name
     * @return SQLGenerator
     */
    public function dropForeign($name)
    {
        $names = (array) $name;

        foreach ($names as $name) {
            $name = sprintf("%s_%s_foreign", $this->getTable(), $name);
            $this->sqls[] = sprintf('DROP FOREIGN KEY `%s`', $name);
        }

        return $this;
    }

    /**
     * Add table index;
     *
     * @param string $name
     * @return SQLGenerator
     */
    public function addIndex($name)
    {
        if ($this->scope == 'alter') {
            $command = 'ADD INDEX';
        } else {
            $command = 'INDEX';
        }

        $this->sqls[] = sprintf('%s `%s`', $command, $name);

        return $this;
    }

    /**
     * Drop table index;
     *
     * @param string $name
     * @return SQLGenerator
     */
    public function dropIndex($name)
    {
        $names = (array) $name;

        foreach ($names as $name) {
            $this->sqls[] = sprintf('DROP INDEX `%s`', $name);
        }

        return $this;
    }

    /**
     * Drop primary column;
     *
     * @return SQLGenerator
     */
    public function dropPrimary()
    {
        $this->sqls[] = 'DROP PRIMARY KEY';

        return $this;
    }

    /**
     * Add table unique;
     *
     * @param string $name
     * @return SQLGenerator
     */
    public function addUnique($name)
    {
        if ($this->scope == 'alter') {
            $command = 'ADD UNIQUE';
        } else {
            $command = 'UNIQUE';
        }

        $this->sqls[] = sprintf('%s `%s`', $command, $name);

        return $this;
    }

    /**
     * Drop table unique;
     *
     * @param string $name
     * @return SQLGenerator
     */
    public function dropUnique($name)
    {
        $names = (array) $name;

        foreach ($names as $name) {
            $this->sqls[] = sprintf('DROP UNIQUE `%s`', $name);
        }

        return $this;
    }
}
