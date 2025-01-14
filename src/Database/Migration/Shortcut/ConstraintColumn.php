<?php

declare(strict_types=1);

namespace Bow\Database\Migration\Shortcut;

use Bow\Database\Migration\SQLGenerator;

trait ConstraintColumn
{
    /**
     * Add Foreign KEY constraints
     *
     * @param string $name
     * @param array $attributes
     * @return SQLGenerator
     */
    public function addForeign(string $name, array $attributes = []): SQLGenerator
    {
        if ($this->scope == 'alter') {
            $command = 'ADD CONSTRAINT';
        } else {
            $command = 'CONSTRAINT';
        }

        $on = '';
        $references = '';

        if ($this->adapter == "pgsql") {
            $target = sprintf("\"%s_%s_foreign\"", $this->getTable(), $name);
        } else {
            $target = sprintf("%s_%s_foreign", $this->getTable(), $name);
        }

        if (isset($attributes['on'])) {
            $on = strtoupper(' ON ' . $attributes['on']);
        }

        if (isset($attributes['references'], $attributes['table'])) {
            if ($this->adapter === 'pgsql') {
                $references = sprintf(
                    ' REFERENCES %s("%s")',
                    $attributes['table'],
                    $attributes['references']
                );
            } else {
                $references = sprintf(
                    ' REFERENCES %s(`%s`)',
                    $attributes['table'],
                    $attributes['references']
                );
            }
        }

        if ($this->adapter === 'pgsql') {
            $replacement = '%s %s FOREIGN KEY ("%s")%s%s';
        } else {
            $replacement = '%s %s FOREIGN KEY (`%s`)%s%s';
        }

        $sql = sprintf(
            $replacement,
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
     * @param bool $as_raw
     * @return SQLGenerator
     */
    public function dropForeign(string|array $name, bool $as_raw = false): SQLGenerator
    {
        $names = (array) $name;

        foreach ($names as $name) {
            if (!$as_raw) {
                $name = sprintf("%s_%s_foreign", $this->getTable(), $name);
            }
            if ($this->adapter === 'pgsql') {
                $this->sqls[] = sprintf('DROP FOREIGN KEY `%s`', $name);
            } else {
                $this->sqls[] = sprintf('DROP FOREIGN KEY %s', $name);
            }
        }

        return $this;
    }

    /**
     * Add table index;
     *
     * @param string $name
     * @return SQLGenerator
     */
    public function addIndex(string $name): SQLGenerator
    {
        if ($this->scope == 'alter') {
            $command = 'ADD INDEX';
        } else {
            $command = 'INDEX';
        }

        if ($this->adapter === 'pgsql') {
            $this->sqls[] = sprintf('%s %s', $command, $name);
        } else {
            $this->sqls[] = sprintf('%s `%s`', $command, $name);
        }

        return $this;
    }

    /**
     * Drop table index;
     *
     * @param string $name
     * @return SQLGenerator
     */
    public function dropIndex(string $name): SQLGenerator
    {
        $names = (array) $name;

        foreach ($names as $name) {
            if ($this->adapter === 'pgsql') {
                $this->sqls[] = sprintf('DROP INDEX %s', $name);
            } else {
                $this->sqls[] = sprintf('DROP INDEX `%s`', $name);
            }
        }

        return $this;
    }

    /**
     * Drop primary column;
     *
     * @return SQLGenerator
     */
    public function dropPrimary(): SQLGenerator
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
    public function addUnique(string $name): SQLGenerator
    {
        if ($this->scope == 'alter') {
            $command = 'ADD UNIQUE';
        } else {
            $command = 'UNIQUE';
        }

        if ($this->adapter === 'pgsql') {
            $this->sqls[] = sprintf('%s %s', $command, $name);
        } else {
            $this->sqls[] = sprintf('%s `%s`', $command, $name);
        }

        return $this;
    }

    /**
     * Drop table unique;
     *
     * @param string $name
     * @return SQLGenerator
     */
    public function dropUnique(string $name): SQLGenerator
    {
        $names = (array) $name;

        foreach ($names as $name) {
            if ($this->adapter === 'pgsql') {
                $this->sqls[] = sprintf('DROP UNIQUE %s', $name);
            } else {
                $this->sqls[] = sprintf('DROP UNIQUE `%s`', $name);
            }
        }

        return $this;
    }
}
