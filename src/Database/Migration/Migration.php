<?php

declare(strict_types=1);

namespace Bow\Database\Migration;

use Bow\Console\Color;
use Bow\Database\Database;
use Bow\Database\Migration\SQLGenerator;
use Bow\Database\Exception\MigrationException;
use Bow\Database\Connection\AbstractConnection;

abstract class Migration
{
    /**
     * The connexion adapter
     *
     * @var AbstractConnection
     */
    private $adapter;

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
     * @param string $name
     * @return Migration
     */
    final public function connection(string $name): Migration
    {
        Database::connection($name);

        $this->adapter = Database::getConnectionAdapter();

        return $this;
    }

    /**
     * Drop table action
     *
     * @param string $table
     * @return Migration
     */
    final public function drop(string $table): Migration
    {
        $table = $this->getTablePrefixed($table);

        $sql = sprintf('DROP TABLE %s;', $table);

        return $this->executeSqlQuery($sql);
    }

    /**
     * Drop table if he exists action
     *
     * @param string $table
     * @return Migration
     */
    final public function dropIfExists(string $table): Migration
    {
        $table = $this->getTablePrefixed($table);

        if ($this->adapter->getName() === 'pgsql') {
            $sql = sprintf('DROP TABLE IF EXISTS %s CASCADE;', $table);
        } else {
            $sql = sprintf('DROP TABLE IF EXISTS %s;', $table);
        }

        return $this->executeSqlQuery($sql);
    }

    /**
     * Function of creation of a new table in the database.
     *
     * @param string  $table
     * @param callable $cb
     * @return Migration
     */
    final public function create(string $table, callable $cb): Migration
    {
        $table = $this->getTablePrefixed($table);

        call_user_func_array($cb, [
            $generator = new SQLGenerator($table, $this->adapter->getName(), 'create')
        ]);

        if ($this->adapter->getName() == 'mysql') {
            $engine = sprintf(' ENGINE=%s', strtoupper($generator->getEngine()));
        } else {
            $engine = null;
        }

        if ($this->adapter->getName() !== 'pgsql') {
            $sql = sprintf("CREATE TABLE `%s` (%s)%s;", $table, $generator->make(), $engine);

            return $this->executeSqlQuery($sql);
        }

        foreach ($generator->getCustomTypeQueries() as $sql) {
            try {
                $this->executeSqlQuery($sql);
            } catch (\Exception $exception) {
                echo sprintf("%s\n", Color::yellow("Warning: " . $exception->getMessage()));
            }
        }

        $sql = sprintf("CREATE TABLE %s (%s)%s;", $table, $generator->make(), $engine);
        return $this->executeSqlQuery($sql);
    }

    /**
     * Alter table action.
     *
     * @param string $table
     * @param callable $cb
     * @return Migration
     */
    final public function alter(string $table, callable $cb): Migration
    {
        $table = $this->getTablePrefixed($table);

        call_user_func_array($cb, [
            $generator = new SQLGenerator($table, $this->adapter->getName(), 'alter')
        ]);

        if ($this->adapter->getName() === 'pgsql') {
            $sql = sprintf('ALTER TABLE %s %s;', $table, $generator->make());
        } else {
            $sql = sprintf('ALTER TABLE `%s` %s;', $table, $generator->make());
        }

        return $this->executeSqlQuery($sql);
    }

    /**
     * Add SQL query
     *
     * @param string $sql
     * @return Migration
     */
    final public function addSql(string $sql): Migration
    {
        return $this->executeSqlQuery($sql);
    }

    /**
     * Rename table
     *
     * @param string $table
     * @param string $to
     * @return Migration
     */
    final public function renameTable(string $table, string $to): Migration
    {
        $sql = sprintf('ALTER TABLE %s RENAME TO %s', $table, $to);

        return $this->executeSqlQuery($sql);
    }

    /**
     * Rename table if exists
     *
     * @param string $table
     * @param string $to
     * @return Migration
     */
    final public function renameTableIfExists(string $table, string $to): Migration
    {
        $sql = sprintf('ALTER TABLE IF EXISTS %s RENAME TO %s', $table, $to);

        return $this->executeSqlQuery($sql);
    }

    /**
     * Get prefixed table name
     *
     * @param string $table
     * @return string
     */
    final public function getTablePrefixed(string $table): string
    {
        $table = $this->adapter->getTablePrefix() . $table;

        return $table;
    }

    /**
     * Execute direct sql query
     *
     * @param string $sql
     * @return Migration
     * @throws MigrationException
     */
    private function executeSqlQuery(string $sql): Migration
    {
        try {
            Database::statement($sql);
        } catch (\Exception $exception) {
            echo sprintf("%s %s\n", Color::red("▶"), $sql);
            throw new MigrationException($exception->getMessage(), (int) $exception->getCode());
        }

        echo sprintf("%s %s\n", Color::green("▶"), $sql);
        return $this;
    }
}
