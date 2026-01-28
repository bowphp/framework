<?php

declare(strict_types=1);

namespace Bow\Database\Migration;

use Bow\Console\Color;
use Bow\Database\Connection\AbstractConnection;
use Bow\Database\Database;
use Bow\Database\Exception\ConnectionException;
use Bow\Database\Exception\MigrationException;
use Exception;

abstract class Migration
{
    /**
     * The connexion adapter
     *
     * @var AbstractConnection
     */
    private AbstractConnection $adapter;

    /**
     * Create the table if not exists
     *
     * @var bool
     */
    private bool $create_if_not_exists = false;

    /**
     * Migration constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->adapter = Database::getConnectionAdapter();
    }

    /**
     * Up migration
     *
     * @return void
     */
    abstract public function up(): void;

    /**
     * Rollback migration
     *
     * @return void
     */
    abstract public function rollback(): void;

    /**
     * Switch connection
     *
     * @param  string $name
     * @return Migration
     * @throws ConnectionException
     */
    final public function connection(string $name): Migration
    {
        Database::connection($name);

        $this->adapter = Database::getConnectionAdapter();

        return $this;
    }

    /**
     * Get adapter name
     *
     * @return string
     */
    public function getAdapterName(): string
    {
        return $this->adapter->getName();
    }

    /**
     * Drop table action
     *
     * @param  string $table
     * @param  bool   $displayInfo
     * @return Migration
     * @throws MigrationException
     */
    final public function drop(string $table, bool $displayInfo = true): Migration
    {
        $table = $this->getTablePrefixed($table);

        $sql = sprintf('DROP TABLE %s;', $table);

        return $this->executeSqlQuery($sql, $displayInfo);
    }

    /**
     * Get prefixed table name
     *
     * @param  string $table
     * @return string
     */
    final public function getTablePrefixed(string $table): string
    {
        return $this->adapter->getTablePrefix() . $table;
    }

    /**
     * Drop table if he exists action
     *
     * @param  string $table
     * @param  bool   $displayInfo
     * @return Migration
     * @throws MigrationException
     */
    final public function dropIfExists(string $table, bool $displayInfo = true): Migration
    {
        $table = $this->getTablePrefixed($table);

        if ($this->adapter->getName() === 'pgsql') {
            $sql = sprintf('DROP TABLE IF EXISTS %s CASCADE;', $table);
        } else {
            $sql = sprintf('DROP TABLE IF EXISTS %s;', $table);
        }

        return $this->executeSqlQuery($sql, $displayInfo);
    }

    /**
     * Function of creation of a new table in the database.
     *
     * @param  string   $table
     * @param  callable $cb
     * @param  bool     $displayInfo
     * @return Migration
     * @throws MigrationException
     */
    final public function create(string $table, callable $cb, bool $displayInfo = true): Migration
    {
        $table = $this->getTablePrefixed($table);

        call_user_func_array($cb, [
            $generator = new Table($table, $this->adapter->getName(), 'create')
        ]);

        if ($this->adapter->getName() == 'mysql') {
            $engine = sprintf(' ENGINE=%s', strtoupper($generator->getEngine()));
        } else {
            $engine = null;
        }

        if ($this->adapter->getName() !== 'pgsql') {
            $sql = sprintf("CREATE TABLE %s%s (%s)%s;", $this->create_if_not_exists ? 'IF NOT EXISTS ' : '', $table, $generator->make(), $engine);

            return $this->executeSqlQuery($sql, $displayInfo);
        }

        foreach ($generator->getCustomTypeQueries() as $sql) {
            try {
                $this->executeSqlQuery($sql, $displayInfo);
            } catch (Exception $exception) {
                echo sprintf("%s\n", Color::yellow("Warning: " . $exception->getMessage()));
            }
        }

        $sql = sprintf("CREATE TABLE %s%s (%s)%s;", $this->create_if_not_exists ? 'IF NOT EXISTS ' : '', $table, $generator->make(), $engine);

        $this->create_if_not_exists = false;

        return $this->executeSqlQuery($sql, $displayInfo);
    }

    /**
     * Create the table if not exists
     *
     * @param  string   $table
     * @param  callable $cb
     * @param  bool     $displayInfo
     * @return Migration
     * @throws MigrationException
     */
    public function createIfNotExists(string $table, callable $cb, bool $displayInfo = true): Migration
    {
        $this->create_if_not_exists = true;

        return $this->create($table, $cb, $displayInfo);
    }

    /**
     * Alter table action.
     *
     * @param  string   $table
     * @param  callable $cb
     * @param  bool     $displayInfo
     * @return Migration
     * @throws MigrationException
     */
    final public function alter(string $table, callable $cb, bool $displayInfo = true): Migration
    {
        $table = $this->getTablePrefixed($table);

        call_user_func_array($cb, [
            $generator = new Table($table, $this->adapter->getName(), 'alter')
        ]);

        $sql_definition = $generator->make();

        if ($this->adapter->getName() === 'pgsql') {
            $sql = sprintf('ALTER TABLE %s %s;', $table, $sql_definition);
        } else {
            $sql = sprintf('ALTER TABLE `%s` %s;', $table, $sql_definition);
        }

        if (empty($sql_definition)) {
            return $this;
        }

        return $this->executeSqlQuery($sql, $displayInfo);
    }

    /**
     * Add SQL query
     *
     * @param  string $sql
     * @return Migration
     * @throws MigrationException
     * @deprecated Use sql() instead.
     */
    final public function addSql(string $sql): Migration
    {
        return $this->executeSqlQuery($sql);
    }

    /**
     * Execute SQL query
     *
     * @param  string $sql
     * @return Migration
     * @throws MigrationException
     */
    final public function sql(string $sql): Migration
    {
        return $this->executeSqlQuery($sql);
    }

    /**
     * Rename table
     *
     * @param  string $table
     * @param  string $to
     * @param  bool   $displayInfo
     * @return Migration
     * @throws MigrationException
     */
    final public function renameTable(string $table, string $to, bool $displayInfo = true): Migration
    {
        $sql = sprintf('ALTER TABLE %s RENAME TO %s', $table, $to);

        return $this->executeSqlQuery($sql, $displayInfo);
    }

    /**
     * Rename table if exists
     *
     * @param  string $table
     * @param  string $to
     * @param  bool   $displayInfo
     * @return Migration
     * @throws MigrationException
     */
    final public function renameTableIfExists(string $table, string $to, bool $displayInfo = true): Migration
    {
        $sql = sprintf('ALTER TABLE IF EXISTS %s RENAME TO %s', $table, $to);

        return $this->executeSqlQuery($sql, $displayInfo);
    }

    /**
     * Execute direct sql query
     *
     * @param  string $sql
     * @return Migration
     * @throws MigrationException
     */
    private function executeSqlQuery(string $sql, bool $displayInfo = true): Migration
    {
        try {
            Database::statement($sql);
        } catch (Exception $exception) {
            echo sprintf("%s %s\n", Color::red("▶"), $sql);
            throw new MigrationException($exception->getMessage(), (int)$exception->getCode());
        }

        if ($displayInfo) {
            echo sprintf("%s %s\n", Color::green("▶"), $sql);
        }

        return $this;
    }
}
