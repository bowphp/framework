<?php

namespace System\Database;

use PDO;
use Exception;
use PDOStatement;
use ErrorException;
use System\Support\Util;
use System\Support\Logger;
use System\Support\Security;
use System\Exception\ConnectionException;

class DB
{
    private static $db = null;
    private static $query = null;
    private static $config;

    private static function loadConfiguration()
    {
        return static::$config = require dirname(dirname(dirname(__DIR__))) . "/configuration/db.php";
    }

    public static function connection($option = null, $cb = null)
    {
        if (static::$db instanceof PDO) {
            return null;
        }
        if ($option !== null) {
            if (is_string($option)) {
                $zone = $option;
            } else {
                $zone = "default";
                $cb = $option;
            }
        } else {
            $zone = "default";
        }
        /**
         * Essaie de connection
         */
        $t = static::loadConfiguration();

        if (is_int($t)) {
            Util::launchCallBack($cb, [new ErrorException("Le fichier db.php est mal configurer")]);
        }
        $c = isset($t->connections[$zone]) ? $t->connections[$zone] : null;
        if (is_null($c)) {
            Util::launchCallBack($cb, [new ErrorException("La clé '$zone' n'est pas définir dans l'entre db.php")]);
        }
        $db = null;
        try {
            // Construction de l'objet PDO
            $dns = $c["scheme"] . ":host=" . $c['host'] . ($c['port'] !== '' ? ":" . $c['port'] : "") . ";dbname=". $c['dbname'];
            if ($c["scheme"] == "pgsql") {
                $dns = str_replace(";", " ", $dns);
            }
            // Connection à la base de donnée.
            static::$db = new PDO($dns, $c['user'], $c['pass'], [
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8",
                PDO::ATTR_DEFAULT_FETCH_MODE => $t->fetch
            ]);
        } catch (PDOException $e) {
            /**
             * Lancement d'exception
             */
            Util::launchCallBack($cb, [$e]);
        }
        Util::launchCallBack($cb, false);
        return static::class;
    }

	/**
	 * switchTo, permet de ce connecter a une autre base de donnee.
     *
     * 
	 * @param string $enterKey
	 * @param callable $cb
	 * @return \System\Snoop
	 */
	public static function switchTo($enterKey, $cb)
	{
        static::verifyConnection();
		if (!is_string($enterKey)) {
			Util::launchCallBack($cb, [new InvalidArgumentException("parametre invalide")]);
		} else {
			static::$db = null;
			static::connection($enterKey, $cb);
		}
		return $this;
	}

    public static function update($sqlstatement, $bind = [])
    {
        static::verifyConnection();
        if (preg_match("/^update\s[\w\d_`]+\s\bset\b\s.+\s\bwhere\b\s.+$/i", $sqlstatement)) {
            if (count($bind) == 0) {
                return static::$db->exec($sqlstatement);
            } else {
                $pdostatement = static::$db->prepare($sqlstatement);
                return $pdostatement->execute(Security::sanitaze($bind, true));
            }
        }
        return false;
    }

    public static function select($sqlstatement, $bind = [])
    {
        static::verifyConnection();
        if (preg_match("/^select\s[\w\d_()*`]+\sfrom\s[\w\d_`]+.+$/i", $sqlstatement)) {
            $pdostatement = static::$db->prepare($sqlstatement);
            $pdostatement->execute($bind);
            $data = $pdostatement->fetchAll();
            if (count($data) == 1) {
                return Security::sanitaze($data[0]);
            } else {
                return Security::sanitaze(array_values($data));
            }
        }
        return null;
    }

    public static function insert($sqlstatement, $bind = [])
    {
        static::verifyConnection();
        if (preg_match("/^insert\sinto\s[\w\d_-`]+\s?(\(.+\)\svalues\(.+\)|\s?set\s(.+)+)$/i", $sqlstatement)) {
            $pdostatement = static::$db->prepare($sqlstatement);
            return $pdostatement->execute(Security::sanitaze($bind, true));
        }
        return null;
    }

    public static function statement($sqlstatement)
    {
        static::verifyConnection();
        if (preg_match("/^(drop|alter|truncate|create\stable)\s.+$/i", $sqlstatement)) {
            return (bool) static::$db->exec($sqlstatement);
        }
        return false;
    }

    public static function delete($sqlstatement, $bind = [])
    {
        static::verifyConnection();
        if (preg_match("/^delete\sfrom\s[\w\d_`]+\swhere\s.+$/i", $sqlstatement)) {
            if (count($bind) == 0) {
                return (bool) static::$db->exec($sqlstatement);
            } else {
                $pdostatement = static::$db->prepare($sqlstatement);
                static::bind($pdostatement, $bind);
                return (bool) $pdostatement->execute();
            }
        }
        return false;
    }

    public static function table($tableName)
    {
        static::verifyConnection();
        return Table::load($tableName, static::$db);
    }

    /**
     * rangeField, fonction permettant de sécuriser les données.
     *
     * 
     * @param array $data, les données à sécuriser
     * @return array $field
     */
    private static function rangeField($data)
    {
        $field = "";
        $i = 0;
        foreach ($data as $key => $value) {
            /**
             * Construction d'une chaine de format:
             * key1 = value1, key2 = value2[, keyN = valueN]
             * Utile pour binder une réquette INSERT en mode preparer:
             */
            $field .= ($i > 0 ? ", " : "") . $key . " = " . $value;
            $i++;
        }
        /**
         * Retourne une chaine de caractère.
         */
        return $field;
    }

    private static function bind(PDOStatement &$pdoStatement, $data)
    {
        foreach ($data as $key => $value) {
			if ($value === "NULL") {
                continue;
            }
			$param = PDO::PARAM_INT;
			if (preg_match("/[a-zA-Z_-]+/", $value)) {
				/**
				 * SÉCURIATION DES DONNÉS
				 * - Injection SQL
				 * - XSS
				 */
				$param = PDO::PARAM_STR;
				$value = Security::sanitaze($value, true);
			} else {
				/**
				 * On force la valeur en entier.
				 */
				$value = (int) $value;
			}
			/**
			 * Exécution de bindValue
			 */
            if (is_int($key)) {
    			$pdoStatement->bindValue(":$key", $value, $param);
            } else {
                $pdoStatement->bindValue($key, $value, $param);
            }
		}
        return $pdoStatement;
    }

	/**
	 * Formateur de donnee. key => :value
	 *
     * 
	 * @param array $data
	 * @return array $resultat
	 */
	public function add2points(array $data)
	{
		$resultat = [];
		foreach ($data as $key => $value) {
			$resultat[$value] = ":$value";
		}
		return $resultat;
	}

    /**
     * Insertion des données dans la DB
     * ====================== MODEL ======================
     *	$options = [
     *		"query" => [
     *			"table" => "nomdelatable",
     *			"type" => INSERT|SELECT|DELETE|UPDATE,
     *			"data" => $data2pointAdded
     *		],
     *		"data" => "les données a insérer."
     *	];
     * 
     * 
     * @param array $options
     * @param bool|false $return
     * @param bool|false $lastInsertId
     * @throws \ErrorException
     * @return array|self|\StdClass
     */
    public static function query(array $options, $return = false, $lastInsertId = false)
    {
        static::verifyConnection();
        $sqlStatement = static::makeQuery($options["query"]);
        $pdoStatement = static::$db->prepare($sqlStatement);
        static::bind($pdostatement, isset($options["data"]) ? $options["data"] : []);
        $pdostatement->execute();
        if ($pdostatement->execute()) {
            if ($pdostatement->rowCount() === 0) {
                $data = null;
            } else if ($pdostatement->rowCount() === 1) {
                $data = $pdostatement->fetch();
            } else {
                $data = $pdostatement->fetchAll();
            }
            if ($return == true) {
                if ($lastInsertId == false) {
                    return empty($data) ? null : Security::sanitaze($data);
                }
                return static::$db->lastInsertId();
            }
        } else {
            $debug = $pdoStatement->debugDumpParams();
            Logger::error(__METHOD__."(): Query fails, [SQL: {$debug}]");
        }
        return $this;
    }

    /**
     * Lancement du debut d'un transaction
     * 
     * @var void
     */
    public static function transaction()
    {
        static::verifyConnection();
        static::$db->beginTransaction();
    }

    /**
     * Valider une transaction
     * 
     * @var void
     */
    public static function commit()
    {
        static::verifyConnection();
        static::$db->commit();
    }

    /**
     * Annuler une transaction
     * 
     * @var void
     */
    public static function rollback()
    {
        static::verifyConnection();
        static::$db->rollBack();
    }

    /**
     * Lance la verification de l'etablissement de connection
     * 
     * @throws ConnectionException
     * @var void
     */
    private static function verifyConnection()
    {
        if (! (static::$db instanceof PDO)) {
            throw new ConnectionException("Connection non initialisé.", E_ERROR);
        }
    }
    /**
     * Recupere l'identifiant de la derniere enregistrement.
     * 
     * @return int
     */
    public static function lastInsertId()
    {
        static::verifyConnection();
        return static::$db->lastInsertId();
    }

    /**
     * Recupere la derniere erreur sur la l'object PDO
     * 
     * @return array
     */
    public static function getLastErreur()
    {
        return [
            "pdo" => static::$db->errorInfo()
        ];
    }

}
