<?php
namespace Bow\Database\Connection;

use PDO;
use Bow\Database\Tool;

/**
 * Interface ConnectionInterface
 *
 * @package Database\Connection
 */
abstract class AbstractConnection extends Tool
{
    /**
     * @var string
     */
    protected $name = null;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var int
     */
    protected $fetch = \PDO::FETCH_OBJ;

    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * Permet de creer un instance de l'objet PDO
     *
     * @return void
     */
    abstract public function connection();

    /**
     * Permet de recuperer la connection
     *
     * @return PDO
     */
    public function getConnection()
    {
        return $this->pdo;
    }

    /**
     * Permet de recuperer la connection
     *
     * @param PDO $pdo
     */
    public function setConnection(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Permet de retourner le nom de la connectoon
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Permet de définir le mode de récuperation des données.
     *
     * @param int $fetch
     */
    public function setFetchMode($fetch)
    {
        $this->fetch = $fetch;
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, $fetch);
    }

    /**
     * Permet de récupérer la configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Permet de récupérer le prefix des tables
     *
     * @return mixed|string
     */
    public function getTablePrefix()
    {
        return isset($this->config['prefix']) ? $this->config['prefix'] : '';
    }

    /**
     * Permet de récupérer le type d'encodage
     *
     * @return mixed|string
     */
    public function getCharset()
    {
        return isset($this->config['charset']) ? $this->config['charset'] : 'utf8';
    }
}