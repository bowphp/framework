<?php

namespace Bow\Database\Migration\Compose;

trait PgsqlCompose
{
    /**
     * Compose sql statement for pgsql
     *
     * @param string $name
     * @param array $description
     * @return string
     */
    private function composeAddPgsqlColumn(string $name, array $description): string
    {
        $type = $this->normalizeOfType($description['type']);

        $raw_type = strtoupper($type);
        $type = $raw_type;
        $attributes = $description['attributes'];

        // Transform attributes
        $default = $attributes['default'] ?? null;
        $size = $attributes['size'] ?? false;
        $primary = $attributes['primary'] ?? false;
        $increment = $attributes['increment'] ?? false;
        $nullable = $attributes['nullable'] ?? false;
        $unique = $attributes['unique'] ?? false;
        $unsigned = $attributes['unsigned'] ?? false;
        $after = $attributes['after'] ?? false;
        $first = $attributes['first'] ?? false;

        // String to VARCHAR
        if ($raw_type == 'STRING') {
            $type = 'VARCHAR';
            if (!$size) {
                $size = 255;
            }
        }

        // Add column size
        if ($size) {
            if (in_array($raw_type, ['ENUM', 'CHECK'])) {
                $size = (array) $size;
                $size = "'" . implode("', '", $size) . "'";
            }

            if (in_array($raw_type, ['ENUM', 'CHECK', 'VARCHAR', 'STRING'])) {
                $type = sprintf('%s(%s)', $type, $size);
            }
        }

        // Bind auto increment action
        if ($increment) {
            $type = sprintf('%s SERIAL', $type);
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
            if (in_array($raw_type, ['VARCHAR', 'STRING', 'CHAR', 'ENUM'])) {
                $default = "'" . $default . "'";
            } elseif (is_bool($default)) {
                $default = $default ? 'true' : 'false';
            }
            $type = sprintf('%s DEFAULT %s', $type, $default);
        }

        // Add unsigned mention
        if ($unsigned) {
            $type = sprintf('UNSIGNED %s', $type);
        }

        // Add the column position
        if (is_string($after)) {
            $type = sprintf('%s AFTER `%s`', $type, $after);
        }

        if ($first === true) {
            $type = sprintf('%s FIRST', $type);
        }

        return trim(
            sprintf('%s %s %s', $description['command'], $name, $type)
        );
    }

    /**
     * Drop Column action with pgsql
     *
     * @param string $name
     * @return void
     */
    private function dropColumnForPgsql(string $name): void
    {
        $names = (array) $name;

        foreach ($names as $name) {
            $this->sqls[] = trim(sprintf('DROP COLUMN %s', $name));
        }
    }
}
