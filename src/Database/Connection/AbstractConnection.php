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
     * The write (primary) PDO instance
     *
     * @var ?PDO
     */
    protected ?PDO $write_pdo = null;

    /**
     * The read (replica) PDO instance
     *
     * @var ?PDO
     */
    protected ?PDO $read_pdo = null;

    /**
     * The configuration used to build the write connection
     *
     * @var array
     */
    protected array $write_config = [];

    /**
     * The configuration used to build the read connection,
     * or null when the connection is not split (reads use write).
     *
     * @var ?array
     */
    protected ?array $read_config = null;

    /**
     * AbstractConnection constructor.
     *
     * Splits the connection configuration into a write (primary)
     * configuration and an optional read (replica) configuration.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        $this->write_config = $config;
        unset($this->write_config['read']);

        if (isset($config['read']) && is_array($config['read'])) {
            $this->read_config = array_merge($this->write_config, $config['read']);
        } else {
            $this->read_config = null;
        }

        // Validate eagerly so misconfiguration fails fast, while the
        // connection itself is still established lazily on first use.
        $this->validateConfig($this->write_config);

        if ($this->read_config !== null) {
            $this->validateConfig($this->read_config);
        }
    }

    /**
     * Validate the connection configuration.
     *
     * @param  array $config
     * @return void
     */
    abstract protected function validateConfig(array $config): void;

    /**
     * Build a PDO instance from the given configuration.
     *
     * @param  array $config
     * @return PDO
     */
    abstract protected function makePdo(array $config): PDO;

    /**
     * Build (eagerly) the write connection.
     *
     * Kept for backward compatibility with callers that expect to
     * (re)establish the connection explicitly.
     *
     * @return void
     */
    public function connection(): void
    {
        $this->write_pdo = $this->makePdo($this->write_config);
    }

    /**
     * Retrieves the connection (the write/primary connection)
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->getWriteConnection();
    }

    /**
     * Retrieves the write (primary) connection, building it lazily
     *
     * @return PDO
     */
    public function getWriteConnection(): PDO
    {
        if ($this->write_pdo === null) {
            $this->write_pdo = $this->makePdo($this->write_config);
        }

        return $this->write_pdo;
    }

    /**
     * Retrieves the read (replica) connection, building it lazily.
     *
     * Falls back to the write connection when the connection is not split.
     *
     * @return PDO
     */
    public function getReadConnection(): PDO
    {
        if ($this->read_config === null) {
            return $this->getWriteConnection();
        }

        if ($this->read_pdo === null) {
            $this->read_pdo = $this->makePdo($this->read_config);
        }

        return $this->read_pdo;
    }

    /**
     * Whether the write connection has already been established.
     *
     * Used to inspect transaction state without forcing a connection open.
     *
     * @return bool
     */
    public function hasWriteConnection(): bool
    {
        return $this->write_pdo instanceof PDO;
    }

    /**
     * Set the connection (the write/primary connection)
     *
     * @param PDO $pdo
     */
    public function setConnection(PDO $pdo): void
    {
        $this->write_pdo = $pdo;
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
     * @param  int $fetch
     * @return void
     */
    public function setFetchMode(int $fetch): void
    {
        $this->fetch = $fetch;

        foreach ([$this->write_pdo, $this->read_pdo] as $pdo) {
            if ($pdo instanceof PDO) {
                $pdo->setAttribute(
                    PDO::ATTR_DEFAULT_FETCH_MODE,
                    $fetch
                );
            }
        }
    }

    /**
     * Retrieves the configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        return (array)$this->config;
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
        return $this->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Executes PDOStatement::bindValue on an instance of
     *
     * @param PDOStatement $pdo_statement
     * @param array        $bindings
     *
     * @return PDOStatement
     */
    public function bind(PDOStatement $pdo_statement, array $bindings = []): PDOStatement
    {
        foreach ($bindings as $key => $value) {
            if (is_null($value) || strtolower((string)$value) === 'null') {
                $pdo_statement->bindValue(
                    ':' . $key,
                    $value,
                    PDO::PARAM_NULL
                );
                unset($bindings[$key]);
            }
        }

        foreach ($bindings as $key => $value) {
            $param = PDO::PARAM_STR;

            /**
             * We force the value in whole or in real.
             *
             * SECURITY OF DATA
             * - Injection SQL
             * - XSS
             */
            if (is_int($value)) {
                $value = (int) $value;
                $param = PDO::PARAM_INT;
            } elseif (is_float($value)) {
                $value = (float) $value;
            } elseif (is_double($value)) {
                $value = (float) $value;
            } elseif (is_resource($value)) {
                $param = PDO::PARAM_LOB;
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
