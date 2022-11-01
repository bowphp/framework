<?php

declare(strict_types=1);

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
    private string $table;

    /**
     * The query collection after building
     *
     * @var array
     */
    private array $sqls = [];

    /**
     * Defines the scope action
     * CREATE or ALERT
     *
     * @var string
     */
    private string $scope;

    /**
     * Defines the adapter name
     * MYSQL or SQLITE
     *
     * @var string
     */
    private string $adapter;

    /**
     * Defines ENGINE for mysql
     *
     * @var string
     */
    private string $engine;

    /**
     * Defines COLLATION for mysql
     *
     * @var string
     */
    private string $collation;

    /**
     * Defines CHARSET for mysql
     *
     * @var string
     */
    private string $charset;

    /**
     * SQLGenerator constructor
     *
     * @param string $table
     * @param string $adapter
     * @param string $scope
     */
    public function __construct(string $table, string $adapter = 'mysql', string $scope = 'create')
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
    public function make(): string
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
    public function addColumn(string $name, string $type, array $attributes = []): SQLGenerator
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
    public function dropColumn(string $name): SQLGenerator
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
    private function dropColumnOnMysql(string $name): void
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
    private function dropColumnOnSqlite(string $name): void
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
    public function withEngine(string $engine): void
    {
        $this->engine = $engine;
    }

    /**
     * Get the engine
     *
     * @return string
     */
    public function getEngine(): string
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
    public function withCollation(string $collation): void
    {
        $this->collation = $collation;
    }

    /**
     * Get the collation
     *
     * @return string
     */
    public function getCollation(): string
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
    public function withCharset(string $charset): void
    {
        $this->charset = $charset;
    }

    /**
     * Get the charset
     *
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Get the define table name
     *
     * @return string
     */
    public function getTable(): string
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
    private function composeAddColumn(string $name, array $description): string
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
    public function setScope(string $scope): SQLGenerator
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
    public function setAdapter(string $adapter): SQLGenerator
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
    private function prefixColumn(string $name, string $by): string
    {
        return $this->table.'_'.$name.'_'.$by;
    }
}
