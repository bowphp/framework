<?php

declare(strict_types=1);

namespace Bow\Database\Migration;

use Bow\Console\Color;
use Bow\Database\Connection\AbstractConnection;
use Bow\Database\Database;

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

        $sql = sprintf('DROP TABLE `%s`;', $table);

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

        $sql = sprintf('DROP TABLE IF EXISTS `%s`;', $table);

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

        $generator = new SQLGenerator($table, $this->adapter->getName());

        call_user_func_array($cb, [$generator]);

        if ($this->adapter->getName() == 'mysql') {
            $engine = sprintf('ENGINE=%s', strtoupper($generator->getEngine()));
        } else {
            $engine = null;
        }

        $sql = sprintf("CREATE TABLE `%s` (%s) %s;", $table, $generator->make(), $engine);

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

        $sql = sprintf('ALTER TABLE `%s` %s;', $table, $generator->make());

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
     * Add SQL query
     *
     * @param string $table
     * @param string $to
     * @return Migration
     */
    final public function renameTable(string $table, string $to): Migration
    {
        if ($this->adapter->getName() == 'mysql') {
            $command = 'RENAME';
        } else {
            $command = 'ALTER TABLE';
        }

        $sql = sprintf('%s %s TO %s', $command, $table, $to);

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
     */
    private function executeSqlQuery(string $sql): Migration
    {
        try {
            $result = (bool) Database::statement($sql);
        } catch (\Exception $exception) {
            echo sprintf("%s%s\n", Color::red("▶"), $sql);
            throw $exception;
        }

        echo sprintf("%s%s\n", Color::green("▶"), $sql);

        return $this;
    }
}
