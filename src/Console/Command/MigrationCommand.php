<?php

namespace Bow\Console\Command;

use Bow\Console\Color;
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
     *
     * @return void
     * @throws mixed
     */
    public function migrate($model)
    {
        $this->factory($model, 'up');
    }

    /**
     * Rollback migration command
     *
     * @param  string $model
     *
     * @return void
     * @throws mixed
     */
    public function rollback($model)
    {
        $this->factory($model, 'rollback');
    }

    /**
     * Reset migration command
     *
     * @param  string $model
     *
     * @return void
     * @throws mixed
     */
    public function reset($model)
    {
        $this->factory($model, 'reset');
    }

    /**
     * Create a migration in both directions
     *
     * @param string $model
     * @param string $type
     *
     * @return void
     * @throws mixed
     */
    private function factory($model, $type)
    {
        $migrations = [];

        // We include all migrations files and collect it for make great manage
        foreach ($this->getMigrationFiles() as $file) {
            $migrations[$file] = explode('.', basename($file))[0];
        }

        $options = $this->arg->options();

        $this->createMigrationTable();

        // Get current migration status
        $current_migrations = $this->getMigrationTable()
        ->whereIn('migration', array_values($migrations))->get();

        try {
            $action = 'make'.strtoupper($type);

            return $this->$action($current_migrations, $migrations);
        } catch (\Exception $e) {
            $this->printExceptionMessage($e);
        }
    }

    /**
     * Up migration
     *
     * @param array $current_migration
     * @param array $migrations
     *
     * @return void
     */
    private function makeUp($current_migrations, $migrations)
    {
        if (count($current_migrations) == count($migrations)) {
            echo Color::green('Nothing to migrate.');
            
            return;
        }

        foreach ($migrations as $file => $migration) {
            if (! $this->checkMigrationExistance($migration)) {
                require $file;

                // Up migration
                (new $migration)->up();

                // Create new migration status
                $this->createMigrationStatus($migration);
            }
        }

        foreach ($current_migrations as $migration) {
            $this->updateMigrationStatus($migration->migration, $migration->batch + 1);
        }
    }

    /**
     * Rollback migration
     *
     * @param array $current_migration
     * @param array $migrations
     *
     * @return void
     */
    private function makeRollback($current_migrations, $migrations)
    {
        if (count($current_migrations) == 0) {
            echo Color::green('Nothing to rollback.');

            return;
        }

        foreach ($migrations as $file => $migration) {
            foreach ($current_migrations as $value) {
                if (!($value->batch == 1 && $migration == $value->migration)) {
                    continue;
                }

                require $file;

                // Rollabck migration
                (new $migration)->rollback();

                break;
            }
        }

        // Rollback in migration table
        $this->getMigrationTable()->where('batch', 1)->delete();

        foreach ($current_migrations as $value) {
            if ($value->batch != 1) {
                $this->updateMigrationStatus($value->migration, $value->batch - 1);
            }
        }

        echo Color::green('Migration rollback.');
    }

    /**
     * Reset migration
     *
     * @param array $current_migration
     * @param array $migrations
     *
     * @return void
     */
    private function makeReset($current_migrations, $migrations)
    {
        if (count($current_migrations) == 0) {
            echo Color::green('Nothing to reset.');
            
            return;
        }

        // We sort current migration by batch value
        usort($current_migrations, function ($first, $second) {
            return $first->batch > $second->batch;
        });

        foreach ($current_migrations as $value) {
            foreach ($migrations as $file => $migration) {
                if ($value->migration == $migration) {
                    require $file;

                    (new $migration)->rollback();

                    $this->getMigrationTable()->where('migration', $migration)->delete();
                }
            }
        }

        echo Color::green('Migration reset.');
    }

    /**
     * Print the error message
     *
     * @param \Exception $e
     *
     * @return void
     */
    private function printExceptionMessage(\Exception $e)
    {
        $message = Color::red($e->getMessage());
        $migration = Color::yellow($migration);

        exit(sprintf("\nOn %s\n\n%s\n\n", $migration, $message));
    }

    /**
     * Create the migration status table
     *
     * @return void
     */
    private function createMigrationTable()
    {
        $generator = new SQLGenerator(
            config('database.migration'),
            Database::getConnectionAdapter()->getName(),
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
            config('database.migration'),
            $generator->make()
        );

        statement($sql);
    }

    /**
     * Cretae migration status
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
     * Check the migration existance
     *
     * @param string $migration
     *
     * @return bool
     */
    private function checkMigrationExistance($migration)
    {
        $result = $this->getMigrationTable()->where('migration', $migration)->first();

        return !is_null($result);
    }

    /**
     * Get migration table
     *
     * @return \Database\Database\QueryBuilder
     */
    private function getMigrationTable()
    {
        $migration_status_table = config('database.migration');

        return table($migration_status_table);
    }

    /**
     * Get migration parten
     *
     * @return array
     */
    private function getMigrationFiles()
    {
        $file_partern = $this->setting->getMigrationDirectory().strtolower("/*.php");

        return glob($file_partern);
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

        $options = $this->arg->options();

        if ($options->has('--create') && $options->has('--table')) {
            $this->throwFailsCommand('bad command', 'add help');
        }

        $type = "model/standard";

        if ($options->has('--table')) {
            if ($options->get('--table') === true) {
                $this->throwFailsCommand('bad command option [--table=table]', 'add help');
            }

            $table = $options->get('--table');

            $type = 'model/table';
        } elseif ($options->has('--create')) {
            if ($options->get('--create') === true) {
                $this->throwFailsCommand('bad command option [--create=table]', 'add help');
            }

            $table = $options->get('--create');

            $type = 'model/create';
        }

        $generator->write($type, [
            'table' => $table ?? 'table_name',
            'className' => $filename
        ]);

        echo Color::green('The migration file has been successfully created')."\n";
    }
}
