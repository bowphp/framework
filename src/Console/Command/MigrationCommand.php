<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\AbstractCommand;
use Bow\Console\Color;
use Bow\Console\Generator;
use Bow\Database\Database;
use Bow\Database\Exception\ConnectionException;
use Bow\Database\Exception\QueryBuilderException;
use Bow\Database\Migration\Table;
use Bow\Database\QueryBuilder;
use Bow\Support\Str;
use ErrorException;
use Exception;

class MigrationCommand extends AbstractCommand
{
    /**
     * Make a migration command
     *
     * @return void
     * @throws Exception
     */
    public function migrate(): void
    {
        $this->factory('up');
    }

    /**
     * Rollback migration command
     *
     * @return void
     * @throws Exception
     */
    public function rollback(): void
    {
        $this->factory('rollback');
    }

    /**
     * Reset migration command
     *
     * @return void
     * @throws Exception
     */
    public function reset(): void
    {
        $this->factory('reset');
    }

    /**
     * Create a migration in both directions
     *
     * @param  string $type
     * @return void
     * @throws Exception
     */
    private function factory(string $type): void
    {
        $migrations = [];

        // We include all migrations files and collect it for make great manage
        foreach ($this->getMigrationFiles() as $file) {
            $migrations[$file] = explode('.', basename($file))[0];
        }

        // We create the migration database status
        $this->createMigrationTable();

        $action = 'make' . strtoupper($type);

        $this->$action($migrations);
    }

    /**
     * Create the migration status table
     *
     * @return void
     * @throws ConnectionException
     */
    private function createMigrationTable(): void
    {
        $connection = $this->arg->getParameter("--connection", config("database.default"));

        Database::connection($connection);
        $adapter = Database::getConnectionAdapter();

        $table = $adapter->getTablePrefix() . config('database.migration', 'migrations');
        $generator = new Table(
            $table,
            $adapter->getName(),
            'create'
        );

        $generator->addString('migration', ['unique' => true]);
        $generator->addInteger('batch');
        $generator->addDatetime('created_at', [
            'default' => 'CURRENT_TIMESTAMP',
            'nullable' => true
        ]);

        $sql = sprintf(
            'CREATE TABLE IF NOT EXISTS %s (%s);',
            $table,
            $generator->make()
        );

        Database::statement($sql);
    }

    /**
     * Up migration
     *
     * @param  array $migrations
     * @return void
     * @throws ConnectionException
     * @throws QueryBuilderException
     */
    protected function makeUp(array $migrations): void
    {
        // We get migrations
        $current_migrations = $this->getMigrationTable()
            ->whereIn('migration', array_values($migrations))->get();

        if (count($current_migrations) == count($migrations)) {
            echo Color::green('Nothing to migrate.');

            return;
        }

        foreach ($migrations as $file => $migration) {
            if ($this->checkIfMigrationExist($migration)) {
                continue;
            }

            // Include the migration file
            include $file;

            try {
                // Up migration
                (new $migration())->up();
            } catch (Exception $exception) {
                $this->throwMigrationException($exception, $migration);
            }

            // Create new migration status
            $this->createMigrationStatus($migration);
        }

        foreach ($current_migrations as $migration) {
            $this->updateMigrationStatus(
                $migration->migration,
                $migration->batch + 1
            );
        }
    }

    /**
     * Check the migration existence
     *
     * @param  string $migration
     * @return bool
     * @throws ConnectionException|QueryBuilderException
     */
    private function checkIfMigrationExist(string $migration): bool
    {
        $result = $this->getMigrationTable()
            ->where('migration', $migration)
            ->first();

        return !is_null($result);
    }

    /**
     * Throw migration exception
     *
     * @param Exception $exception
     * @param string    $migration
     */
    private function throwMigrationException(Exception $exception, string $migration): void
    {
        $this->printExceptionMessage(
            $exception->getMessage(),
            $migration
        );
    }

    /**
     * Print the error message
     *
     * @param  string $message
     * @param  string $migration
     * @return void
     */
    private function printExceptionMessage(string $message, string $migration): void
    {
        $message = Color::red($message);
        $migration = Color::yellow($migration);

        exit(sprintf("\nOn %s\n\n%s\n\n", $migration, $message));
    }

    /**
     * Create migration status
     *
     * @param  string $migration
     * @return void
     * @throws ConnectionException
     */
    private function createMigrationStatus(string $migration): void
    {
        $table = $this->getMigrationTable();

        $table->insert([
            'migration' => $migration,
            'batch' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update migration status
     *
     * @param  string $migration
     * @param  int    $batch
     * @return void
     * @throws ConnectionException|QueryBuilderException
     */
    private function updateMigrationStatus(string $migration, int $batch): void
    {
        $table = $this->getMigrationTable();

        $table->where('migration', $migration)->update([
            'migration' => $migration,
            'batch' => $batch
        ]);
    }

    /**
     * Rollback migration
     *
     * @param  array $migrations
     * @return void
     * @throws ConnectionException
     * @throws QueryBuilderException
     */
    protected function makeRollback(array $migrations): void
    {
        // We get current migration status
        $current_migrations = $this->getMigrationTable()
            ->whereIn('migration', array_values($migrations))
            ->orderBy("created_at", "desc")
            ->orderBy("migration", "desc")
            ->get();

        if (count($current_migrations) == 0) {
            echo Color::green('Nothing to rollback.');

            return;
        }

        foreach ($current_migrations as $value) {
            foreach ($migrations as $file => $migration) {
                if (
                    !($value->batch == 1
                    && $migration == $value->migration)
                ) {
                    continue;
                }

                // Include the migration file
                include $file;

                // Rollback migration
                try {
                    (new $migration())->rollback();
                } catch (Exception $exception) {
                    $this->throwMigrationException($exception, $migration);
                }

                break;
            }
        }

        // Rollback in migration table
        $this->getMigrationTable()->where('batch', 1)->delete();

        foreach ($current_migrations as $value) {
            if ($value->batch != 1) {
                $this->updateMigrationStatus(
                    $value->migration,
                    $value->batch - 1
                );
            }
        }

        // Print console information
        echo Color::green('Migration rollback.');
    }

    /**
     * Reset migration
     *
     * @param  array $migrations
     * @return void
     * @throws ConnectionException
     * @throws QueryBuilderException
     */
    protected function makeReset(array $migrations): void
    {
        // We get current migration status
        $current_migrations = $this->getMigrationTable()
            ->whereIn('migration', array_values($migrations))
            ->orderBy("created_at", "desc")
            ->orderBy("migration", "desc")
            ->get();

        if (count($current_migrations) == 0) {
            echo Color::green('Nothing to reset.');

            return;
        }

        foreach ($current_migrations as $value) {
            foreach ($migrations as $file => $migration) {
                if ($value->migration != $migration) {
                    continue;
                }

                // Include the migration file
                include $file;

                // Rollback migration
                try {
                    (new $migration())->rollback();
                } catch (Exception $exception) {
                    $this->throwMigrationException($exception, $migration);
                }

                $this->getMigrationTable()->where('migration', $migration)->delete();
            }
        }

        // Print console information
        echo Color::green('Migration reset.');
    }

    /**
     * Get migration pattern
     *
     * @return array
     */
    private function getMigrationFiles(): array
    {
        $file_pattern = $this->setting->getMigrationDirectory() . strtolower("/*.php");

        return glob($file_pattern);
    }

    /**
     * Get migration table
     *
     * @return QueryBuilder
     * @throws ConnectionException
     */
    private function getMigrationTable(): QueryBuilder
    {
        $migration_status_table = config('database.migration', 'migrations');

        return db_table($migration_status_table);
    }
}
