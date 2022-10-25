<?php declare(strict_types=1);

namespace Bow\Database\Connection;

use PDO;

abstract class AbstractConnection
{
    /**
     * The connexion name
     *
     * @var string
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
    abstract public function connection();

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
    public function setConnection(PDO $pdo)
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
     */
    public function setFetchMode(int $fetch)
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
        return isset($this->config['prefix'])
            ? $this->config['prefix']
            : '';
    }

    /**
     * Retrieves the type of encoding
     *
     * @return string
     */
    public function getCharset(): string
    {
        return isset($this->config['charset'])
            ? $this->config['charset']
            : 'utf8';
    }

    /**
     * Retrieves the define Collation
     *
     * @return string
     */
    public function getCollation(): string
    {
        return isset($this->config['collation'])
            ? $this->config['collation']
            : 'utf8_unicode_ci';
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
}
