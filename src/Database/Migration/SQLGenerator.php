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
     * Change a column in the table
     *
     * @param string $name
     * @param string $type
     * @param array $attributes
     *
     * @return SQLGenerator
     */
    public function changeColumn($name, $type, array $attributes = [])
    {
        $command = 'MODIFY COLUMN';

        $this->sqls[] = $this->composeAddColumn(
            trim($name, '`'),
            compact('name', 'type', 'attributes', 'command')
        );

        return $this;
    }

    /**
     * Change a column in the table
     *
     * @param string $name
     * @param string $new
     *
     * @return SQLGenerator
     */
    public function renameColumn($name, $new)
    {
        if ($this->adapter == 'mysql') {
            $this->sqls[] = sprintf("RENAME COLUMN `%s` TO `%s`", $name, $new);
        } else {
            $this->renameColumnOnSqlite($name, $new);
        }

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
     * Drop Column action with sqlite
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
     * Renom column action with sqlite
     *
     * @param string $name
     * @param string $new
     */
    private function renameColumnOnSqlite($old_name, $new_name)
    {
        $pdo = Database::getPdo();

        $pdo->exec('PRAGMA foreign_keys=off');
        $statement = $pdo->query(sprintf('PRAGMA table_info(%s);', $this->table));

        $statement->execute();
        $select = [];
        $select_old = [];

        foreach ($statement->fetchAll() as $key => $column) {
            $select_old[$key] = $column->name;
            if (property_exists($column, $old_name)) {
                $select[$key] = $column->name;
            } else {
                $select[$key] = $new_name;
            }
        }

        $pdo->exec('BEGIN TRANSACTION;');

        $pdo->exec("ALTER TABLE " . $this->table . " RENAME TO __temp_rename_sqlite_table;");

        $statement = $pdo->exec('PRAGMA foreign_keys=off');

        $pdo->exec(sprintf(
            'CREATE TABLE %s AS SELECT * FROM %s;',
            $this->table,
            '__temp_rename_sqlite_table'
        ));

        $pdo->exec(sprintf(
            "INSERT INTO %s(%s) SELECT %s FROM %s",
            $this->table,
            implode(', ', $select),
            implode(', ', $select_old),
            '__temp_rename_sqlite_table'
        ));

        $pdo->exec("DROP TABLE __temp_rename_sqlite_table;");
        $pdo->exec('COMMIT;');
        $pdo->exec('PRAGMA foreign_keys=on');
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
     * Get the define table name
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
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
        $raw_type = strtoupper($description['type']);
        $type = $raw_type;
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
        $after = $attributes['after'] ?? false;
        $first = isset($attributes['first']);

        // String to VARCHAR
        if ($type == 'STRING') {
            $type = 'VARCHAR';

            if (!$size) {
                $size = 255;
            }
        }

        // Add column size
        if ($size) {
            if ($raw_type === 'ENUM') {
                $size = (array) $size;
                $size = "'" . implode("', '", $size) . "'";
            }
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
        if (!is_null($default)) {
            if (in_array($raw_type, ['VARCHAR', 'STRING', 'CHAR', 'ENUM'])) {
                $default = "'" . $default . "'";
            } elseif (is_bool($default)) {
                $default = $default ? 'true' : 'false';
            }
            $type = sprintf('%s DEFAULT %s', $type, $default);
        }

        if ($check) {
            $type = sprintf('%s CHECK (%s)', $type, $check);
        }

        // Add unsigned mention
        if ($unsigned) {
            $type = sprintf('UNSIGNED %s', $type);
        }

        // Add the column position
        if (is_string($after)) {
            $type = sprintf('%s AFTER %s', $type, $after);
        }

        if ($first) {
            $type = sprintf('%s FIRST', $type);
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
        return $this->table . '_' . $name . '_' . $by;
    }
}
