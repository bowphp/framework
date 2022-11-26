<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\Color;
use Bow\Console\ConsoleInformation;
use Bow\Console\Generator;
use Bow\Database\Database;
use Bow\Database\Migration\SQLGenerator;
use Bow\Support\Str;

class MigrationCommand extends AbstractCommand
{
    /**
     * Make a migration command
     *
     * @param  string $model
     * @return void
     * @throws \Exception
     */
    public function migrate(): void
    {
        $this->factory('up');
    }

    /**
     * Rollback migration command
     *
     * @return void
     * @throws \Exception
     */
    public function rollback(): void
    {
        $this->factory('rollback');
    }

    /**
     * Reset migration command
     *
     * @return void
     * @throws \Exception
     */
    public function reset(): void
    {
        $this->factory('reset');
    }

    /**
     * Create a migration in both directions
     *
     * @param string $model
     * @param string $type
     *
     * @return void
     * @throws \Exception
     */
    private function factory(string $type)
    {
        $migrations = [];

        // We include all migrations files and collect it for make great manage
        foreach ($this->getMigrationFiles() as $file) {
            $migrations[$file] = explode('.', basename($file))[0];
        }

        // We create the migration database status
        $this->createMigrationTable();

        // We get current migration status
        $current_migrations = $this->getMigrationTable()
            ->whereIn('migration', array_values($migrations))->get();

        try {
            $action = 'make' . strtoupper($type);

            return $this->$action($current_migrations, $migrations);
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Up migration
     *
     * @param array $current_migration
     * @param array $migrations
     * @return void
     */
    protected function makeUp(array $current_migrations, array $migrations): void
    {
        if (count($current_migrations) == count($migrations)) {
            echo Color::green('Nothing to migrate.');

            return;
        }

        foreach ($migrations as $file => $migration) {
            if ($this->checkIfMigrationExist($migration)) {
                continue;
            }

            // Include the migration file
            require $file;

            try {
                // Up migration
                (new $migration())->up();
            } catch (\Exception $exception) {
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
     * Rollback migration
     *
     * @param array $current_migration
     * @param array $migrations
     * @return void
     */
    protected function makeRollback(array $current_migrations, array $migrations): void
    {
        if (count($current_migrations) == 0) {
            echo Color::green('Nothing to rollback.');

            return;
        }

        // We sort current migration by created date value
        usort($current_migrations, function ($first, $second) {
            return strtotime($first->created_at) < strtotime($second->created_at);
        });

        foreach ($current_migrations as $value) {
            foreach ($migrations as $file => $migration) {
                if (
                    !($value->batch == 1
                    && $migration == $value->migration)
                ) {
                    continue;
                }

                // Include the migration file
                require $file;

                // Rollback migration
                try {
                    (new $migration())->rollback();
                } catch (\Exception $exception) {
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
     * @param array $current_migration
     * @param array $migrations
     * @return void
     */
    protected function makeReset(array $current_migrations, array $migrations): void
    {
        if (count($current_migrations) == 0) {
            echo Color::green('Nothing to reset.');

            return;
        }

        // We sort current migration by batch or created date value
        usort($current_migrations, function ($first, $second) {
            if ($first->batch == $second->batch) {
                return strtotime($first->created_at) < strtotime($second->created_at);
            }

            return $first->batch > $second->batch;
        });

        foreach ($current_migrations as $value) {
            foreach ($migrations as $file => $migration) {
                if ($value->migration != $migration) {
                    continue;
                }

                // Include the migration file
                require $file;

                // Rollback migration
                try {
                    (new $migration())->rollback();
                } catch (\Exception $exception) {
                    $this->throwMigrationException($exception, $migration);
                }

                $this->getMigrationTable()->where('migration', $migration)->delete();
            }
        }

        // Print console information
        echo Color::green('Migration reset.');
    }

    /**
     * Print the error message
     *
     * @param string $message
     * @param string $migration
     * @return void
     */
    private function printExceptionMessage(string $message, string $migration)
    {
        $message = Color::red($message);
        $migration = Color::yellow($migration);

        exit(sprintf("\nOn %s\n\n%s\n\n", $migration, $message));
    }

    /**
     * Throw migration exception
     *
     * @param \Exception $exception
     * @param string $migration
     */
    private function throwMigrationException(\Exception $exception, string $migration)
    {
        $this->printExceptionMessage(
            $exception->getMessage(),
            $migration
        );
    }

    /**
     * Create the migration status table
     *
     * @return void
     */
    private function createMigrationTable()
    {
        $connection = $this->arg->getParameter("--connection", config("database.default"));

        Database::connection($connection);
        $adapter = Database::getAdapterConnection();

        $table = $adapter->getTablePrefix() . config('database.migration', 'migrations');
        $generator = new SQLGenerator(
            $table,
            $adapter->getName(),
            'create'
        );

        $generator->addColumn('migration', 'string', ['unique' => true]);
        $generator->addColumn('batch', 'int');
        $generator->addColumn('created_at', 'datetime', [
            'default' => 'CURRENT_TIMESTAMP',
            'nullable' => true
        ]);

        $sql = sprintf(
            'CREATE TABLE IF NOT EXISTS %s (%s);',
            $table,
            $generator->make()
        );

        return Database::statement($sql);
    }

    /**
     * Create migration status
     *
     * @param string $migration
     * @param int $batch
     *
     * @return void
     */
    private function createMigrationStatus($migration)
    {
        $table = $this->getMigrationTable();

        return $table->insert([
            'migration' => $migration,
            'batch' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update migration status
     *
     * @param string $migration
     * @param int $batch
     *
     * @return void
     */
    private function updateMigrationStatus($migration, $batch)
    {
        $table = $this->getMigrationTable();

        return $table->where('migration', $migration)->update([
            'migration' => $migration,
            'batch' => $batch
        ]);
    }

    /**
     * Check the migration existence
     *
     * @param string $migration
     * @return bool
     */
    private function checkIfMigrationExist($migration)
    {
        $result = $this->getMigrationTable()
            ->where('migration', $migration)
            ->first();

        return !is_null($result);
    }

    /**
     * Get migration table
     *
     * @return \Database\Database\QueryBuilder
     */
    private function getMigrationTable()
    {
        $migration_status_table = config('database.migration', 'migrations');

        return table($migration_status_table);
    }

    /**
     * Get migration pattern
     *
     * @return array
     */
    private function getMigrationFiles()
    {
        $file_pattern = $this->setting->getMigrationDirectory() . strtolower("/*.php");

        return glob($file_pattern);
    }

    /**
     * Create a migration command
     *
     * @param string $model
     *
     * @return void
     * @throws \ErrorException
     */
    public function generate($model)
    {
        $create_at = date("YmdHis");
        $filename = sprintf("Version%s%s", $create_at, ucfirst(Str::camel($model)));

        $generator = new Generator(
            $this->setting->getMigrationDirectory(),
            $filename
        );

        $parameters = $this->arg->getParameters();

        if ($parameters->has('--create') && $parameters->has('--table')) {
            $this->throwFailsCommand('bad command', 'add help');
        }

        $type = "model/standard";

        if ($parameters->has('--table')) {
            if ($parameters->get('--table') === true) {
                $this->throwFailsCommand('bad command option [--table=table]', 'add help');
            }

            $table = $parameters->get('--table');

            $type = 'model/table';
        } elseif ($parameters->has('--create')) {
            if ($parameters->get('--create') === true) {
                $this->throwFailsCommand('bad command option [--create=table]', 'add help');
            }

            $table = $parameters->get('--create');

            $type = 'model/create';
        }

        $generator->write($type, [
            'table' => $table ?? 'table_name',
            'className' => $filename
        ]);

        // Print console information
        echo Color::green('The migration file has been successfully created') . "\n";
    }
}
