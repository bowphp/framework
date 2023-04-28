<?php

declare(strict_types=1);

namespace Bow\Database\Migration;

use Bow\Database\Exception\SQLGeneratorException;

class SQLGenerator
{
    use Shortcut\NumberColumn;
    use Shortcut\MixedColumn;
    use Shortcut\TextColumn;
    use Shortcut\DateColumn;
    use Shortcut\ConstraintColumn;
    use Compose\MysqlCompose;
    use Compose\SqliteCompose;
    use Compose\PgsqlCompose;

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
    public function __construct(string $table, string $adapter, string $scope)
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
     * @param array $attribute
     * @return SQLGenerator
     */
    public function addColumn(string $name, string $type, array $attribute = []): SQLGenerator
    {
        if ($this->scope == 'alter') {
            $command = 'ADD COLUMN';
        } else {
            $command = null;
        }

        $this->sqls[] = $this->composeAddColumn(
            trim($name, '`'),
            compact('name', 'type', 'attribute', 'command')
        );

        return $this;
    }

    /**
     * Change a column in the table
     *
     * @param string $name
     * @param string $type
     * @param array $attributes
     * @return SQLGenerator
     */
    public function changeColumn(string $name, string $type, array $attribute = []): SQLGenerator
    {
        $command = 'MODIFY COLUMN';

        $this->sqls[] = $this->composeAddColumn(
            trim($name, '`'),
            compact('name', 'type', 'attribute', 'command')
        );

        return $this;
    }

    /**
     * Rename a column in the table
     *
     * @param string $name
     * @param string $new
     * @return SQLGenerator
     */
    public function renameColumn(string $name, string $new): SQLGenerator
    {
        if (!in_array($this->adapter, ['mysql', 'pgsql'])) {
            $this->renameColumnOnSqlite($name, $new);

            return $this;
        }

        if ($this->adapter === 'pgsql') {
            $this->sqls[] = sprintf("RENAME COLUMN %s TO %s", $name, $new);
        } else {
            $this->sqls[] = sprintf("RENAME COLUMN `%s` TO `%s`", $name, $new);
        }

        return $this;
    }

    /**
     * Drop table column
     *
     * @param string $name
     * @return SQLGenerator
     */
    public function dropColumn(string $name): SQLGenerator
    {
        if ($this->adapter === 'mysql') {
            $this->dropColumnForMysql($name);
        } elseif ($this->adapter === 'pgsql') {
            $this->dropColumnForPgsql($name);
        } else {
            $this->dropColumnForSqlite($name);
        }

        return $this;
    }

    /**
     * Set the engine
     *
     * @param string $engine
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
     * Set the define table name
     *
     * @param string $table
     * @return string
     */
    public function setTable(string $table): string
    {
        $this->table = $table;

        return $this->table;
    }

    /**
     * Compose sql instruction
     *
     * @param string $name
     * @param array $description
     * @return string
     */
    private function composeAddColumn(string $name, array $description): string
    {
        if (isset($attribute['size']) && in_array($description["attribute"]["type"], ['blob', 'json', 'character'])) {
            $type = strtoupper($description["attribute"]["type"]);
            throw new SQLGeneratorException("Cannot define size for $type type");
        }

        switch ($this->adapter) {
            case "sqlite":
                return $this->composeAddSqliteColumn($name, $description);
            case "mysql":
                return $this->composeAddMysqlColumn($name, $description);
            case "pgsql":
                return $this->composeAddPgsqlColumn($name, $description);
        }
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
     * Normalize the data type
     *
     * @param string $type
     * @return string
     */
    public function normalizeOfType(string $type)
    {
        if (in_array($this->adapter, ["mysql", "pgsql"])) {
            return $type;
        }

        if (preg_match('/int|float|double/', $type)) {
            $type = 'integer';
        } elseif (preg_match('/float|double/', $type)) {
            $type = 'real';
        } elseif (preg_match('/^(text|char|string)$/i', $type)) {
            $type = 'text';
        }

        return $type;
    }

    /**
     * Prefix column name by an other pieces
     *
     * @param string $name
     * @param string $by
     * @return string
     */
    private function prefixColumn(string $name, string $by): string
    {
        return $this->table . '_' . $name . '_' . $by;
    }
}
