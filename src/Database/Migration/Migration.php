<?php

namespace Bow\Database\Migration;

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

        $this->createRepository();
    }

    /**
     * Up migration
     *
     * @return void
     */
    abstract public function up();

    /**
     * Rollback migration
     *
     * @return void
     */
    abstract public function rollback();

    /**
     * Drop table action
     *
     * @param string $table
     *
     * @return Migration
     */
    final public function drop($table)
    {
        $table = $this->getTablePrefixed($table);

        $sql = sprintf('DROP TABLE `%s`;', $table);

        return $this->executeSqlQuery($sql);
    }

    /**
     * Drop table if he exists action
     *
     * @param string $table
     *
     * @return Migration
     */
    final public function dropIfExists($table)
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
     *
     * @return Migration
     */
    final public function create($table, callable $cb)
    {
        $table = $this->getTablePrefixed($table);

        $generator = new SQLGenerator($table, $this->adapter->getName());

        call_user_func_array($cb, [$generator]);

        if ($this->adapter->getName() == 'mysql') {
            $engine = sprintf('ENGINE=%s', strtoupper($generator->getEngine()));
        } else {
            $engine = null;
        }

        $sql = sprintf("CREATE TABLE `%s` (%s)%s;", $table, $generator->make(), $engine);

        return $this->executeSqlQuery($sql);
    }

    /**
     * Alter table action.
     *
     * @param string $table
     * @param callable $cb
     *
     * @return Migration
     */
    final public function alter($table, callable $cb)
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
     *
     * @return Migration
     */
    final public function addSql($sql)
    {
        return $this->executeSqlQuery($sql);
    }

    /**
     * Add SQL query
     *
     * @param string $sql
     *
     * @return Migration
     */
    final public function renameTable($table, $to)
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
     *
     * @return string
     */
    final public function getTablePrefixed($table)
    {
        $table = $this->adapter->getTablePrefix().$table;

        return $table;
    }

    /**
     * Execute direct sql query
     *
     * @param string $sql
     *
     * @return Migration
     */
    private function executeSqlQuery($sql)
    {
        try {
            $result = (bool) Database::statement($sql);
        } catch (\Exception $e) {
            echo "\n\033[0;31m▶\033[00m $sql\n";
            
            throw $e;
        }

        echo "\033[0;32m▶\033[00m $sql\n";

        return $this;
    }

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    private function createRepository()
    {
        // The migrations table is responsible for keeping track of which of the
        // migrations have actually run for the application. We'll create the
        // table to hold the migration file's path as well as the batch ID.
        $generator = new SQLGenerator(null);

        $generator->addColumn('id', 'integer', ['primary' => true]);
        $generator->addColumn('migration', 'string', ['unique' => true]);
        $generator->addColumn('batch', 'integer', ['size' => 11, 'default' => 0]);

        $sql = sprintf(
            'CREATE TABLE IF NOT EXISTS %s (%s)',
            'bow_migration_registers',
            $generator->make()
        );

        Database::statement($sql);
    }
}
