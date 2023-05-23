<?php

namespace Bow\Database\Migration\Compose;

use Bow\Database\Exception\SQLGeneratorException;

trait MysqlCompose
{
    /**
     * Compose sql statement for mysql
     *
     * @param string $name
     * @param array $description
     * @return string
     */
    private function composeAddMysqlColumn(string $name, array $description): string
    {
        $type = $this->normalizeOfType($description['type']);

        $raw_type = strtoupper($type);
        $type = $raw_type;
        $attribute = $description['attribute'];

        if (in_array($type, ['TEXT']) && isset($attribute['default'])) {
            throw new SQLGeneratorException("Cannot define default value for $type type");
        }

        // Transform attribute
        $default = $attribute['default'] ?? null;
        $size = $attribute['size'] ?? false;
        $check = $attribute['check'] ?? false;
        $primary = $attribute['primary'] ?? false;
        $increment = $attribute['increment'] ?? false;
        $nullable = $attribute['nullable'] ?? false;
        $unique = $attribute['unique'] ?? false;
        $unsigned = $attribute['unsigned'] ?? false;
        $after = $attribute['after'] ?? false;
        $first = $attribute['first'] ?? false;
        $custom = $attribute['custom'] ?? false;

        // String to VARCHAR
        if ($raw_type == 'STRING') {
            $type = 'VARCHAR';
        }

        if (!$size && in_array($raw_type, ['VARCHAR', 'STRING', 'LONG VARCHAR'])) {
            $size = 255;
        }

        // Set the size
        if ($size) {
            $type = sprintf('%s(%s)', $type, $size);
        }

        // Add column size
        if (in_array($raw_type, ['ENUM', 'CHECK'])) {
            $check = (array) $check;
            $check = "'" . implode("', '", $check) . "'";
            $type = sprintf('%s(%s)', $type, $check);
        }

        // Bind auto increment action
        if ($increment) {
            $type = sprintf('%s AUTO_INCREMENT', $type);
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

        // Add unsigned mention
        if ($unsigned) {
            $type = sprintf('%s UNSIGNED', $type);
        }

        // Add default value
        if (!is_null($default)) {
            if (in_array($raw_type, ['VARCHAR', 'LONG VARCHAR', 'STRING', 'CHAR',  'CHARACTER', 'ENUM', 'TEXT'])) {
                $default = "'" . addcslashes($default, "'") . "'";
            } elseif (is_bool($default)) {
                $default = $default ? 'true' : 'false';
            }
            $type = sprintf('%s DEFAULT %s', $type, $default);
        }

        // Add the column position
        if (is_string($after)) {
            $type = sprintf('%s AFTER `%s`', $type, $after);
        }

        if ($first === true) {
            $type = sprintf('%s FIRST', $type);
        }

        // Apply the custom definition
        if ($custom) {
            $type = sprintf('%s %s', $type, $custom);
        }

        return trim(
            sprintf('%s `%s` %s', $description['command'], $name, $type)
        );
    }

    /**
     * Drop Column action with mysql
     *
     * @param string $name
     * @return void
     */
    private function dropColumnForMysql(string $name): void
    {
        $names = (array) $name;

        foreach ($names as $name) {
            $this->sqls[] = trim(sprintf('DROP COLUMN `%s`', $name));
        }
    }
}
