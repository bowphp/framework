<?php
namespace Bow\Database\Connection;

use PDO;
use Bow\Database\Util\DBUtility;
use PDOStatement;

/**
 * Interface ConnectionInterface
 *
 * @package Database\Connection
 */
abstract class AbstractConnection
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

    /**
     * Éxécute PDOStatement::bindValue sur une instance de PDOStatement passé en paramètre
     *
     * @param PDOStatement $pdoStatement
     * @param array $data
     *
     * @return PDOStatement
     */
    public function bind(PDOStatement $pdoStatement, array $data = [])
    {
        foreach ($data as $key => $value) {
            if (is_null($value) || strtolower($value) === 'null') {
                $pdoStatement->bindValue(':' . $key, $value, PDO::PARAM_NULL);
                unset($data[$key]);
            }
        }

        foreach ($data as $key => $value) {
            $param = PDO::PARAM_INT;

            if (preg_match('/[a-zA-Z_-]+|éàèëïùöôîüµ$£!?\.\+,;:/', $value)) {
                /**
                 * SÉCURIATION DES DONNÉS
                 * - Injection SQL
                 * - XSS
                 */
                $param = PDO::PARAM_STR;
            } else {
                /**
                 * On force la valeur en entier ou en réél.
                 */
                if (is_int($value)) {
                    $value = (int) $value;
                } elseif (is_float($value)) {
                    $value = (float) $value;
                } else {
                    $value = (double) $value;
                }
            }

            if (is_string($key)) {
                $pdoStatement->bindValue(':' . $key, $value, $param);
            } else {
                $pdoStatement->bindValue($key + 1, $value, $param);
            }
        }

        return $pdoStatement;
    }
}