<?php

namespace Bow\Database\Migration;

use Bow\Database\Database;
use Bow\Exception\ModelException;
use Bow\Support\Str;

class SQLGenerator
{
    use Shortcut\NumberColumn;
    use Shortcut\MixedColumn;
    use Shortcut\TextColumn;
    use Shortcut\DateColumn;
    use Shortcut\ConstraintColumn;
    
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
     * Defines ENGINE for mysql
     *
     * @var string
     */
    private $engine;

    /**
     * Defines COLLATION for mysql
     *
     * @var string
     */
    private $collation;

    /**
     * Defines CHARSET for mysql
     *
     * @var string
     */
    private $charset;

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

        $this->engine = 'InnoDB';

        $this->collation = 'utf8_unicode_ci';

        $this->charset = 'utf8';
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
        
        $select = [];

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
     * Set the engine
     *
     * @param string $engine
     *
     * @return void
     */
    public function withEngine($engine)
    {
        $this->engine = $engine;
    }

    /**
     * Get the engine
     *
     * @return string
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * Set the collation
     *
     * @param string $collation
     *
     * @return void
     */
    public function withCollation($collation)
    {
        $this->collation = $collation;
    }

    /**
     * Get the collation
     *
     * @return string
     */
    public function getCollation()
    {
        return $this->collation;
    }

    /**
     * Set the charset
     *
     * @param string $charset
     *
     * @return void
     */
    public function withCharset($charset)
    {
        $this->charset = $charset;
    }

    /**
     * Get the charset
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
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
        $check = $attributes['check'] ?? false;
        $unsigned = $attributes['unsigned'] ?? false;

        // String to VARCHAR
        if ($type == 'STRING') {
            $type = 'VARCHAR';

            if (!$size) {
                $size = 255;
            }
        }

        // Wrap default value
        if (in_array($type, ['VARCHAR', 'CHAR'])) {
            if (!is_null($default)) {
                $default = "'".$default."'";
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

        if ($check) {
            $type = sprintf('%s CHECK (%s)', $type, $check);
        }

        // Add unsigned mention
        if ($unsigned) {
            $type = sprintf('UNSIGNED %s', $type);
        }

        return trim(
            sprintf('%s `%s` %s', $description['command'], $name, $type)
        );
    }

    /**
     * Set the scope
     *
     * @param string $scope
     * @return SQLGenerator
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Set the adapter
     *
     * @param string $adapter
     * @return SQLGenerator
     */
    public function setAdapter($adapter)
    {
        $this->adapter = $adapter;

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
