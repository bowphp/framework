<?php

namespace System\Database;

use PDO;
use PDOStatement;
use System\Util\Logger;
use System\Util\Util;
use System\Util\Serurity;

class DB
{
    private static $db = null;
    private static $query = null;

    public static function connection($option = null, $cb = null)
    {
        if (!is_file(dirname(__DIR__) . "/configuration/db.php")) {
            Util::launchCallBack($cb, [new \Exception("Le fichier de configuration n'existe pas. Veuillez le configurer.", E_ERROR)]);
        }
        if (self::$db instanceof PDO) {
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
        $t = require dirname(__DIR__) . "/../configuration/db.php";

        if ($t == 1) {
            Util::launchCallBack($cb, [new \ErrorException("Le fichier .env.php est mal configurer")]);
        }
        $c = isset($t["connections"][$zone]) ? $t$t["connections"][$zone] : null;
        if ($c === null) {
            Util::launchCallBack($cb, [new \ErrorException("La clé '$zone' n'est pas définir dans l'entre .env.php")]);
        }
        $db = null;
        try {
            // Construction de l'objet PDO
            $dns = $c["scheme"] . ":host=" . $c['host'] . ($c['port'] !== '' ? ":" . $c['port'] : "") . ";dbname=". $c['dbname'];
            if ($c["scheme"] == "pgsql") {
                $dns = str_replace(";", " ", $dns);
            }
            // Connection à la base de donnée.
            self::$db = new PDO($dns, $c['user'], $c['pass'], [
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8",
                PDO::ATTR_DEFAULT_FETCH_MODE => $t["connections"]["fetch"]
            ]);
        } catch (PDOException $e) {
            /**
             * Lancement d'exception
             */
            Util::launchCallBack($cb, [$e]);
        }
        Util::launchCallBack($cb, false);
        return self::class;
    }

	/**
	 * switchTo, permet de ce connecter a une autre base de donnee.
	 * @param string $enterKey
	 * @param callable $cb
	 * @return \System\Snoop
	 */
	public static function switchTo($enterKey, $cb)
	{
		if (!is_string($enterKey)) {
			Util::launchCallBack($cb, [new \InvalidArgumentException("parametre invalide")]);
		} else {
			self::$db = null;
			self::connection($enterKey, $cb);
		}
		return $this;
	}

    public static function update($sqlstatement, $bind = [])
    {
        if (preg_match("/^update\s[\w\d_`]\sset\s.+\swhere\s.+$/i", $sqlstatement)) {
            self::$query = $sqlstatement;
        }
    }

    public static function select($sqlstatement, $bind)
    {
        if (preg_match("/^select\s[\w\d_`]+form\s[\w\d_`]+.+$/i", $sqlstatement)) {
            self::$query = $sqlstatement;
        }
    }

    public static function statement($sqlstatement, $bind = [])
    {
        if (preg_match("/^update\s[\w\d_`]\sset\s.+\swhere\s.+$/i", $sqlstatement)) {
            $sqlstatement = Security::sanitaze($sqlstatement, true);
            return self::$db->exec($sqlstatement);
        } else {
            if (count($bind) == 0) {
                return self::$db->exec($sqlstatement);
            } else {
                $pdostement = self::$db->prepare($sqlstatement);
                self::bind($pdostement, $bind);
                return $pdostement->execute();
            }
        }
    }

    public static function delete($sqlstatement, $bind)
    {
        if (preg_match("/^delete\s[\w\d_`]+\sfrom\s[\w\d_`]+(\swhere\s.+)?$/i", $sqlstatement)) {
            self::$query = $sqlstatement;
        }
    }

    public static function table($tableName)
    {
        retrun Table::load($tableName, self::$db);
    }

    /**
     * rangeField, fonction permettant de sécuriser les données.
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

    private static function bind(PDOStatement $pdoStatement, $data)
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
				$value = Serurity::sanitaze($value, true);
			} else {
				/**
				 * On force la valeur en entier.
				 */
				$value = (int) $value;
			}
			/**
			 * Exécution de bindValue
			 */
			$pdoStatement->bindValue(":$key", $value, $param);
		}
    }
	/**
	 * Formateur de donnee. key => :value
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
     * @param array $options
     * @param bool|false $return
     * @param bool|false $lastInsertId
     * @throws \ErrorException
     * @return array|self|\StdClass
     */
    public function query(array $options, $return = false, $lastInsertId = false)
    {
        $sqlStatement = self::makeQuery($options["query"]);
        $pdoStatement = self::$db->prepare($sqlStatement);
        $r = self::bindValueAndExecuteQuery(isset($options["data"]) ? $options["data"] : [], $pdoStatement, true);
        //
        if (!$r->error) {
            if ($return == true) {
                if ($lastInsertId == false) {
                    return empty($r->data) ? null : Security::sanitaze($r->data);
                }
                return self::$db->lastInsertId();
            }
        } else {
            Logger::error(__METHOD__."() Query fails");
        }
        return $this;
    }

}
