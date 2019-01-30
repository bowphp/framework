<?php

namespace Bow\Database\Migration;

use Bow\Database\Database;
use Bow\Exception\ModelException;
use Bow\Support\Str;

class SQLGenerator
{
    /**
     * The managed table name
     *
     * @var string
     */
    private $table;

    /**
     * The query collection after building
     *
     * @var array
     */
    private $sqls = [];

    /**
     * Defines the scope action
     * CREATE or ALERT
     *
     * @var string
     */
    private $scope;

    /**
     * Defines the adapter name
     * MYSQL or SQLITE
     *
     * @var string
     */
    private $adapter;

    /**
     * SQLGenerator constructor
     *
     * @param string $table
     * @param string $adapter
     * @param string $scope
     */
    public function __construct($table, $adapter = 'mysql', $scope = 'create')
    {
        $this->table = $table;

        $this->scope = $scope;

        $this->adapter = $adapter;
    }

    /**
     * Generate the sql
     *
     * @return string
     */
    public function make()
    {
        $sql = trim(implode(', ', $this->sqls));

        $this->sqls = [];

        return $sql;
    }

    /**
     * Add new column in the table
     *
     * @param string $name
     * @param string $type
     * @param array $attributes
     *
     * @return SQLGenerator
     */
    public function addColumn($name, $type, array $attributes = [])
    {
        if ($this->scope == 'alter') {
            $command = 'ADD COLUMN';
        } else {
            $command = null;
        }

        $this->sqls[] = $this->composeAddColumn(
            trim($name, '`'),
            compact('name', 'type', 'attributes', 'command')
        );

        return $this;
    }

    /**
     * Drop table column
     *
     * @param string $name
     *
     * @return SQLGenerator
     */
    public function dropColumn($name)
    {
        if ($this->adapter == 'mysql') {
            $this->dropColumnOnMysql($name);
        } else {
            $this->dropColumnOnSqlite($name);
        }

        return $this;
    }

    /**
     * Drop Column action with mysql
     *
     * @param string $name
     *
     * @return void
     */
    private function dropColumnOnMysql($name)
    {
        $names = (array) $name;

        foreach ($names as $name) {
            $this->sqls[] = trim(sprintf('DROP COLUMN `%s`', $name));
        }
    }

    /**
     * Drop Column action with mysql
     *
     * @param string $name
     */
    private function dropColumnOnSqlite($name)
    {
        $pdo = Database::getPdo();

        $names = (array) $name;

        $statement = $pdo->query(sprintf('PRAGMA table_info(%s);', $this->table));

        $statement->execute();

        $sql = [];

        foreach ($statement->fetchAll() as $column) {
            if (!in_array($column->name, $names)) {
                $select[] = $column->name;
            }
        }

        $pdo->exec('BEGIN TRANSACTION;');

        $pdo->exec(sprintf(
            'CREATE TABLE __temp_sqlite_table AS SELECT %s FROM %s;',
            implode(', ', $select),
            $this->table
        ));

        $pdo->exec(sprintf('DROP TABLE %s;', $this->table));

        $pdo->exec(sprintf('ALTER TABLE __temp_sqlite_table RENAME TO %s;', $this->table));

        $pdo->exec('COMMIT;');
    }

    /**
     * Add default timestamps
     *
     * @return SQLGenerator
     */
    public function addTimestamps()
    {
        $this->addColumn('created_at', 'datetime');
        $this->addColumn('updated_at', 'datetime');

        return $this;
    }

    /**
     * Set the engine
     *
     * @return void
     */
    public function engine($engine)
    {
        $this->engine = $engine;
    }

    /**
     * Add constraintes
     *
     * @param string $name
     * @param array $attributes
     *
     * @return SQLGenerator
     */
    public function addForeign($name, array $attributes)
    {
        if ($this->scope == 'alter') {
            $command = 'ADD CONSTRAINT';
        } else {
            $command = 'CONSTRAINTES';
        }

        $sql = sprintf(
            '%s %s FOREIGN KEY (%s) REFERENCES %s(%s) %s',
            $command,
            $name,
            $attributes['target'],
            $attributes['on'],
            $attributes['references'],
            $attributes['delete'] ?? $attributes['update'] ?? ''
        );

        $this->sqls[] = $sql;

        return $this;
    }

    /**
     * Drop constraintes column;
     *
     * @param string $name
     *
     * @return SQLGenerator
     */
    public function dropForeign($name)
    {
        $names = (array) $name;

        foreach ($names as $name) {
            $this->sqls[] = sprintf('DROP FOREIGN KEY %s', $name);
        }

        return $this;
    }

    /**
     * Add table index;
     *
     * @param string $name
     *
     * @return SQLGenerator
     */
    public function addIndex($name)
    {
        if ($this->scope == 'alter') {
            $command = 'ADD INDEX';
        } else {
            $command = 'INDEX';
        }

        $this->sqls[] = sprintf('%s %s', $command, $name);

        return $this;
    }

    /**
     * Drop table index;
     *
     * @param string $name
     *
     * @return SQLGenerator
     */
    public function dropIndex($name)
    {
        $names = (array) $name;

        foreach ($names as $name) {
            $this->sqls[] = sprintf('DROP INDEX %s', $name);
        }

        return $this;
    }

    /**
     * Drop primary column;
     *
     * @param string $name
     *
     * @return SQLGenerator
     */
    public function dropPrimary()
    {
        $this->sqls[] = 'DROP PRIMARY KEY';

        return $this;
    }

    /**
     * Compose sql instruction
     *
     * @param string $name
     * @param array $description
     *
     * @return string
     */
    private function composeAddColumn($name, array $description)
    {
        $type = strtoupper($description['type']);
        $attributes = $description['attributes'];

        // Transform attributes
        $default = $attributes['default'] ?? null;
        $size = $attributes['size'] ?? false;
        $primary = $attributes['primary'] ?? false;
        $increment = $attributes['increment'] ?? false;
        $nullable = $attributes['nullable'] ?? false;
        $unique = $attributes['unique'] ?? false;

        // String to VARCHAR
        if ($type == 'STRING') {
            $type = 'VARCHAR';

            if (!$size) {
                $size = 255;
            }
        }

        // Add column size
        if ($size) {
            $type = sprintf('%s(%s)', $type, $size);
        }

        // Bind auto increment action
        if ($increment) {
            $type = sprintf('%s AUTO_INCREMENT', $type);
        }

        // Set column as primary key
        if ($primary) {
            $type = sprintf('%s PRIMARY KEY', $type);
        }

        // Set column as unique
        if ($unique) {
            $type = sprintf('%s UNIQUE', $type);
        }

        // Add null or not null
        if ($nullable) {
            $type = sprintf('%s NULL', $type);
        } else {
            $type = sprintf('%s NOT NULL', $type);
        }

        // Add default value
        if ($default) {
            $type = sprintf('%s DEFAULT %s', $type, $default);
        }

        return trim(
            sprintf('%s `%s` %s', $description['command'], $name, $type)
        );
    }

    /**
     * Set the scope
     *
     * @param string $scope
     *
     * @return SQLGenerator
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Prefix column name by an other pieces
     *
     * @param string $name
     * @param string $by
     *
     * @return string
     */
    private function prefixColumn($name, $by)
    {
        return $this->table.'_'.$name.'_'.$by;
    }
}
