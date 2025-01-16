<?php

namespace Bow\Database\Migration\Compose;

use Bow\Database\Exception\SQLGeneratorException;

trait PgsqlCompose
{
    /**
     * Define the query for create the custom type
     *
     * @var array
     */
    protected array $custom_types = [];

    /**
     * Get the custom type for pgsql
     *
     * @return array
     */
    public function getCustomTypeQueries(): array
    {
        return $this->custom_types;
    }

    /**
     * Compose sql statement for pgsql
     *
     * @param string $name
     * @param array $description
     * @return string
     * @throws SQLGeneratorException
     */
    private function composeAddPgsqlColumn(string $name, array $description): string
    {
        $type = $this->normalizeOfType($description['type']);

        $raw_type = strtoupper($type);
        $type = $raw_type;
        $attribute = $description['attribute'];

        if ($type == 'TEXT' && isset($attribute['default'])) {
            throw new SQLGeneratorException("Cannot define default value for $type type");
        }

        // Transform attribute
        $default = $attribute['default'] ?? null;
        $size = $attribute['size'] ?? false;
        $primary = $attribute['primary'] ?? false;
        $increment = $attribute['increment'] ?? false;
        $nullable = $attribute['nullable'] ?? false;
        $unique = $attribute['unique'] ?? false;
        $unsigned = $attribute['unsigned'] ?? false;
        $after = $attribute['after'] ?? false;
        $first = $attribute['first'] ?? false;
        $custom = $attribute['custom'] ?? false;

        if ($after || $first) {
            throw new SQLGeneratorException("The key first and after only work on mysql");
        }

        // String to VARCHAR
        if ($raw_type == 'STRING') {
            $type = 'VARCHAR';
        }

        // Redefine the size
        if (!$size && in_array($raw_type, ['VARCHAR', 'STRING', 'LONG VARCHAR'])) {
            $size = 255;
        }

        // Add column size
        if (in_array($raw_type, ['VARCHAR', 'STRING', 'LONG VARCHAR']) && $size) {
            $type = sprintf('%s(%s)', $type, $size);
        }

        if (in_array($raw_type, ['ENUM', 'CHECK'])) {
            $type = $this->formatCheckOrEnum($name, $raw_type, $attribute);
        }

        // Bind precision
        if ($raw_type == "DOUBLE") {
            $type = sprintf('DOUBLE PRECISION', $type);
        }

        // Bind auto increment action
        if ($increment) {
            $type = in_array($raw_type, ["INT", "TINYINT", "SMALLINT"]) ? "SERIAL" : "BIGSERIAL";
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
            $strings = ['VARCHAR', 'LONG VARCHAR', 'STRING', 'CHAR',  'CHARACTER', 'ENUM', 'CHECK', 'TEXT'];
            if (in_array($raw_type, $strings)) {
                $default = "'" . addcslashes($default, "'") . "'";
            } elseif (is_bool($default)) {
                $default = $default ? 'true' : 'false';
            }
            $type = sprintf('%s DEFAULT %s', $type, $default);
        }

        // Add unsigned mention
        if ($unsigned) {
            $type = sprintf('UNSIGNED %s', $type);
        }

        // Apply the custom definition
        if ($custom) {
            $type = sprintf('%s %s', $type, $custom);
        }

        return trim(
            sprintf('%s "%s" %s', $description['command'], $name, $type)
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

    /**
     * Format the CHECK in ENUM
     *
     * @param string $name
     * @param string $type
     * @param array $attribute
     * @return string
     */
    private function formatCheckOrEnum(string $name, string $type, array $attribute): string
    {
        if ($type == "ENUM") {
            $size = (array) $attribute['size'];
            $size = "'" . implode("', '", $size) . "'";
            $table = preg_replace("/(ies)$/", "y", $this->table);
            $table = preg_replace("/(s)$/", "", $table);
            $this->custom_types[] = sprintf(
                "CREATE TYPE %s_%s_enum AS ENUM(%s);",
                $table,
                $name,
                $size
            );

            return sprintf('%s_%s_enum', $table, $name);
        }

        if (count($attribute["check"]) === 3) {
            [$column, $comparison, $value] = $attribute["check"];
            if (is_array($value)) {
                $value = "('" . implode("', '", $value) . "')";
            }
            return sprintf('TEXT CHECK ("%s" %s %s)', $column, $comparison, $value);
        }

        [$column, $value] = $attribute["check"];

        $comparison = "=";

        if (is_string($value)) {
            $value = "'" . addcslashes($value, "'") . "'";
            return sprintf('TEXT CHECK ("%s" %s %s)', $column, $comparison, $value);
        }

        $value = (array) $value;

        if (count($value) > 1) {
            $comparison = "IN";

            foreach ($value as $key => $item) {
                if (is_string($item)) {
                    $value[$key] = "'" . addcslashes($item, "'") . "'";
                }
            }

            $value = "(" . implode(", ", $value) . ")";
            return sprintf('TEXT CHECK ("%s" %s %s)', $column, $comparison, $value);
        }

        $value = end($value);

        return sprintf('TEXT CHECK ("%s" %s %s)', $column, $comparison, $value);
    }
}
