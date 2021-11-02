<?php

namespace Bow\Database\Connection;

use Bow\Database\Tool;
use PDO;

abstract class AbstractConnection
{
    /**
     * The connexion name
     *
     * @var string
     */
    protected $name = null;

    /**
     * The configuration definition
     *
     * @var array
     */
    protected $config = [];

    /**
     * The PDO fetch mode
     *
     * @var int
     */
    protected $fetch = \PDO::FETCH_OBJ;

    /**
     * The PDO instance
     *
     * @var PDO
     */
    protected $pdo;

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
    public function getConnection()
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the data recovery mode.
     *
     * @param int $fetch
     */
    public function setFetchMode($fetch)
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
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Retrieves the table prefix
     *
     * @return mixed|string
     */
    public function getTablePrefix()
    {
        return isset($this->config['prefix'])
            ? $this->config['prefix']
            : '';
    }

    /**
     * Retrieves the type of encoding
     *
     * @return mixed|string
     */
    public function getCharset()
    {
        return isset($this->config['charset'])
            ? $this->config['charset']
            : 'utf8';
    }

    /**
     * Retrieves the define Collation
     *
     * @return mixed|string
     */
    public function getCollation()
    {
        return isset($this->config['collation'])
            ? $this->config['collation']
            : 'utf8_unicode_ci';
    }
}
