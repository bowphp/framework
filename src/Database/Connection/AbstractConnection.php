<?php

declare(strict_types=1);

namespace Bow\Database\Connection;

use PDO;
use PDOStatement;

abstract class AbstractConnection
{
    /**
     * The connexion name
     *
     * @var ?string
     */
    protected ?string $name = null;

    /**
     * The configuration definition
     *
     * @var array
     */
    protected array $config = [];

    /**
     * The PDO fetch mode
     *
     * @var int
     */
    protected int $fetch = PDO::FETCH_OBJ;

    /**
     * The PDO instance
     *
     * @var PDO
     */
    protected PDO $pdo;

    /**
     * Create an instance of the PDO
     *
     * @return void
     */
    abstract public function connection(): void;

    /**
     * Retrieves the connection
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Set the connection
     *
     * @param PDO $pdo
     */
    public function setConnection(PDO $pdo): void
    {
        $this->pdo = $pdo;
    }

    /**
     * Returns the name of the connection
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the data recovery mode.
     *
     * @param int $fetch
     * @return void
     */
    public function setFetchMode(int $fetch): void
    {
        $this->fetch = $fetch;

        $this->pdo->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            $fetch
        );
    }

    /**
     * Retrieves the configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        return (array) $this->config;
    }

    /**
     * Retrieves the table prefix
     *
     * @return string
     */
    public function getTablePrefix(): string
    {
        return $this->config['prefix'] ?? '';
    }

    /**
     * Retrieves the type of encoding
     *
     * @return string
     */
    public function getCharset(): string
    {
        return $this->config['charset'] ?? 'utf8';
    }

    /**
     * Retrieves the define Collation
     *
     * @return string
     */
    public function getCollation(): string
    {
        return $this->config['collation'] ?? 'utf8_unicode_ci';
    }

    /**
     * Get the drive that PDO run on
     *
     * @return string
     */
    public function getPdoDriver(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Executes PDOStatement::bindValue on an instance of
     *
     * @param PDOStatement $pdo_statement
     * @param array $bindings
     *
     * @return PDOStatement
     */
    public function bind(PDOStatement $pdo_statement, array $bindings = []): PDOStatement
    {
        foreach ($bindings as $key => $value) {
            if (is_null($value) || strtolower((string) $value) === 'null') {
                $pdo_statement->bindValue(
                    ':' . $key,
                    $value,
                    PDO::PARAM_NULL
                );
                unset($bindings[$key]);
            }
        }

        foreach ($bindings as $key => $value) {
            $param = PDO::PARAM_INT;

            /**
             * We force the value in whole or in real.
             *
             * SECURITY OF DATA
             * - Injection SQL
             * - XSS
             */
            if (is_int($value)) {
                $value = (int) $value;
            } elseif (is_float($value)) {
                $value = (float) $value;
            } elseif (is_double($value)) {
                $value = (float) $value;
            } elseif (is_resource($value)) {
                $param = PDO::PARAM_LOB;
            } else {
                $param = PDO::PARAM_STR;
            }

            // Bind by value with native pdo statement object
            $pdo_statement->bindValue(
                is_string($key) ? ":" . $key : $key + 1,
                $value,
                $param
            );
        }

        return $pdo_statement;
    }
}
