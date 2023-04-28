<?php

namespace Bow\Database\Migration\Compose;

use Bow\Database\Database;
use Bow\Database\Exception\SQLGeneratorException;

trait SqliteCompose
{
    /**
     * Compose sql statement for sqlite
     *
     * @param string $name
     * @param array $description
     * @return string
     */
    private function composeAddSqliteColumn(string $name, array $description): string
    {
        $type = $this->normalizeOfType($description['type']);
        $raw_type = strtoupper($type);

        if (in_array($raw_type, ['ENUM', 'CHECK'])) {
            throw new SQLGeneratorException("Cannot define $raw_type on SQLITE.");
        }

        $type = $raw_type;
        $attribute = $description['attribute'];

        // Transform attribute
        $default = $attribute['default'] ?? null;
        $size = $attribute['size'] ?? false;
        $primary = $attribute['primary'] ?? false;
        $increment = $attribute['increment'] ?? false;
        $nullable = $attribute['nullable'] ?? false;
        $unique = $attribute['unique'] ?? false;

        // String to VARCHAR
        if ($raw_type == 'STRING') {
            $type = 'VARCHAR';
        }

        if (!$size && in_array($raw_type, ['VARCHAR', 'STRING', 'LONG VARCHAR'])) {
            $size = 255;
        }

        // Add column size
        if ($size) {
        }

        // Bind auto increment action
        if ($increment) {
            $type = sprintf('%s AUTOINCREMENT', $type);
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
            if (in_array($raw_type, ['TEXT'])) {
                $default = "'" . addcslashes($default, "'") . "'";
            } elseif (is_bool($default)) {
                $default = $default ? 'true' : 'false';
            }
            $type = sprintf('%s DEFAULT %s', $type, $default);
        }

        return trim(
            sprintf('%s `%s` %s', $description['command'], $name, $type)
        );
    }

    /**
     * Rename column with sqlite
     *
     * @param string $name
     * @param string $new
     * @return void
     */
    private function renameColumnOnSqlite(string $old_name, string $new_name): void
    {
        $pdo = Database::getPdo();

        $pdo->exec('PRAGMA foreign_keys=off');
        $statement = $pdo->query(sprintf('PRAGMA table_info(%s);', $this->table));

        $statement->execute();
        $selects = [];
        $old_selects = [];

        foreach ($statement->fetchAll() as $key => $column) {
            $old_selects[$key] = $column->name;
            if (property_exists($column, $old_name)) {
                $selects[$key] = $column->name;
            } else {
                $selects[$key] = $new_name;
            }
        }

        $pdo->exec('BEGIN TRANSACTION;');
        $pdo->exec("ALTER TABLE " . $this->table . " RENAME TO __temp_rename_sqlite_table;");
        $pdo->exec('PRAGMA foreign_keys=off');

        $pdo->exec(sprintf(
            'CREATE TABLE %s AS SELECT * FROM %s;',
            $this->table,
            '__temp_rename_sqlite_table'
        ));

        $pdo->exec(sprintf(
            "INSERT INTO %s(%s) SELECT %s FROM %s",
            $this->table,
            implode(', ', $selects),
            implode(', ', $old_selects),
            '__temp_rename_sqlite_table'
        ));

        $pdo->exec("DROP TABLE __temp_rename_sqlite_table;");
        $pdo->exec('COMMIT;');
        $pdo->exec('PRAGMA foreign_keys=on;');
    }

    /**
     * Drop Column action with mysql
     *
     * @param string|array $name
     */
    private function dropColumnForSqlite(string|array $name): void
    {
        $pdo = Database::getPdo();

        $names = (array) $name;
        $statement = $pdo->query(sprintf('PRAGMA table_info(%s);', $this->table));
        $statement->execute();

        $columns = [];

        foreach ($statement->fetchAll() as $column) {
            if (!in_array($column->name, $names)) {
                $columns[] = $column->name;
            }
        }

        if (count($columns) === 0) {
            return;
        }

        $pdo->exec("PRAGMA foreign_keys=off;");
        $pdo->exec('BEGIN TRANSACTION;');
        $pdo->exec(sprintf(
            'CREATE TABLE __temp_sqlite_%s_table AS SELECT %s FROM %s;',
            $this->table,
            implode(', ', $columns),
            $this->table,
        ));
        $pdo->exec(sprintf('DROP TABLE %s;', $this->table));
        $pdo->exec(sprintf('ALTER TABLE __temp_sqlite_%s_table RENAME TO %s;', $this->table, $this->table));
        $pdo->exec('COMMIT;');
        $pdo->exec('PRAGMA foreign_keys=on;');
    }
}
