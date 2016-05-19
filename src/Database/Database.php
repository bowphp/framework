<?php

namespace Bow\Database;

use PDO;
use StdClass;
use PDOStatement;
use PDOException;
use ErrorException;
use Bow\Support\Str;
use Bow\Support\Util;
use Bow\Support\Logger;
use Bow\Support\Security;
use InvalidArgumentException;
use Bow\Exception\DatabaseException;
use Bow\Exception\ConnectionException;

class Database extends DatabaseTools
{
    /**
     * Instance de PDO
     *
     * @var \PDO
     */
    private static $db = null;

    /**
     * @var Database
     */
    private static $instance = null;
    /**
     * Configuration
     *
     * @var StdClass
     */
    private static $config;
    /**
     * Configuration
     *
     * @var string
     */
    private static $zone = null;
    /***
     * Liste des constantes d'execution de Requete SQL.
     * Pour le system de de base de donnée ultra minimalise de Bow.
     */
    const SELECT = 1;
    const UPDATE = 2;
    const DELETE = 3;
    const INSERT = 4;

    private final function __construct(){}
    private final function __clone(){}
    /**
     * Charger la configuration
     *
     * @param object $config
     * @return array
     */
    public static function configure($config)
    {
        if (static::$instance === null) {
            static::$instance = new self();
            static::$config = (object) $config;
        }
        return static::$config;
    }

    /**
     * @return Database
     */
    public static function takeInstance()
    {
        static::verifyConnection();
        return static::$instance;
    }
    /**
     * connection, lance la connection sur la DB
     *
     * @param null $zone
     * @param null $cb
     * @return null|Database
     */
    public static function connection($zone = null, $cb = null)
    {
        if (static::$db instanceof PDO) {
            return static::takeInstance();
        }

        if (is_callable($zone)) {
            $cb = $zone;
            $zone = null;
        }


        if (! static::$config instanceof StdClass) {
            Util::launchCallback($cb, [new ConnectionException("Le fichier database.php est mal configurer")]);
        }

        if ($zone == null) {
            $zone = static::$config->default;
        }

        static::$zone = $zone;

        $c = isset(static::$config->connections[static::$zone]) ? static::$config->connections[static::$zone] : null;

        if (is_null($c)) {
            Util::launchCallback($cb, [new ConnectionException("La clé '". static::$zone . "' n'est pas définir dans l'entre database.php")]);
        }

        $db = null;

        try {
            // Variable contenant les informations sur
            // utilisateur
            $username = null;
            $password = null;

            // Configuration suppelement coté PDO
            $pdoPostConfiguation = [
                PDO::ATTR_DEFAULT_FETCH_MODE => static::$config->fetch
            ];

            switch($c["scheme"]) {
                case "mysql":
                    // Construction de la dsn
                    $dns = "mysql:host=" . $c["mysql"]['hostname'] . ($c["mysql"]['port'] !== null ? ":" . $c["mysql"]["port"] : "") . ";dbname=". $c["mysql"]['database'];
                    $username = $c["mysql"]["username"];
                    $password = $c["mysql"]["password"];
                    $pdoPostConfiguation[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES " . Str::upper($c["mysql"]["charset"]);
                    break;
                case "sqlite":
                    $dns = $c["sqlite"]["driver"] . ":" . $c["sqlite"]["database"];
                    break;
                default:
                    throw new DatabaseException("Vérifiez la configuration de la base de donnée.", E_USER_ERROR);
                    break;
            }
            
            // Connection à la base de donnée.
            static::$db = new PDO($dns, $username, $password, $pdoPostConfiguation);

        } catch (PDOException $e) {
            /**
             * Lancement d'exception
             */
            Util::launchCallback($cb, [$e]);
        }

        Util::launchCallback($cb, false);
        
        return static::class;
    }

	/**
	 * switchTo, permet de ce connecter a une autre base de donnée.
     *
	 * @param string $newZone
	 * @param callable $cb
	 * @return void
	 */
	public static function switchTo($newZone, $cb = null)
	{
		if (!is_string($newZone)) {
        	throw new InvalidArgumentException("Paramètre invalide", E_USER_ERROR);
        }

        if($newZone !== static::$zone) {
            static::$db = null;
            static::$zone = $newZone;
            static::connection($newZone, $cb);
        }

        if (is_callable($cb)) {
            static::$db = null;
            static::$zone = "default";
        }
	}

    /**
     * currentZone, retourne la zone courante.
     * 
     * @return string|null
     */
    public static function currentZone()
    {
        return static::$zone;
    }

    /**
     * éxécute une requête update
     *
     * @param string $sqlstatement
     * @param array $bind
     * @return bool
     */
    public static function update($sqlstatement, array $bind = [])
    {
        static::verifyConnection();

        if (preg_match("/^update\s[\w\d_`]+\s\bset\b\s.+\s\bwhere\b\s.+$/i", $sqlstatement)) {
            return static::executePrepareQuery($sqlstatement, $bind);
        }

        return false;
    }

    /**
     * éxécute une requête select
     *
     * @param $sqlstatement
     * @param array $bind
     * @return mixed|null
     */
    public static function select($sqlstatement, array $bind = [])
    {
        static::verifyConnection();

        if (preg_match("/^select\s.+?\sfrom\s.+;?$/i", $sqlstatement)) {

            $pdostatement = static::$db->prepare($sqlstatement);
            static::bind($pdostatement, $bind);
            $pdostatement->execute();

            static::$errorInfo = $pdostatement->errorInfo();

            return Security::sanitaze($pdostatement->fetchAll());
        }

        return null;
    }

    /**
     * éxécute une requête select et retourne un seul enregistrement
     *
     * @param $sqlstatement
     * @param array $bind
     * @return mixed|null
     */
    public static function selectOne($sqlstatement, array $bind = [])
    {
        static::verifyConnection();

        if (preg_match("/^select\s.+?\sfrom\s.+;?$/i", $sqlstatement)) {

            $pdostatement = static::$db->prepare($sqlstatement);
            static::bind($pdostatement, $bind);
            $pdostatement->execute();

            static::$errorInfo = $pdostatement->errorInfo();
            
            return Security::sanitaze($pdostatement->fetch());
        }

        return null;
    }

    /**
     * éxécute une requête insert
     *
     * @param $sqlstatement
     * @param array $bind
     * @return null
     */
    public static function insert($sqlstatement, array $bind = [])
    {
        static::verifyConnection();

        if (preg_match("/^insert\sinto\s[\w\d_-`]+\s?(\(.+\)?\s(values\s?\(.+\),?)+|\s?set\s(.+)+);?$/i", $sqlstatement)) {

            $r = 0;
            $is_2_m_array = true;

            if (isset($bind[0])) {
                if (is_array($bind[0])) {
                    foreach ($bind as $key => $value) {
                        $r += static::executePrepareQuery($sqlstatement, $value);
                    }
                } else {
                    $r = static::executePrepareQuery($sqlstatement, $bind);
                }
            }

            return $r;
        }

        return null;
    }

    /**
     * éxécute une requête de type DROP|CREATE TABLE|TRAUNCATE|ALTER TABLE
     *
     * @param $sqlstatement
     * @return bool
     */
    public static function statement($sqlstatement)
    {
        static::verifyConnection();

        if (preg_match("/^(drop|alter\stable|truncate|create\stable|call)\s.+;?$/i", $sqlstatement)) {
            $r = static::$db->exec($sqlstatement);

            if ($r === 0) {
                $r = true;
            }

            return $r;
        }

        return false;
    }

    /**
     * éxécute une requête delete
     *
     * @param $sqlstatement
     * @param array $bind
     * @return bool
     */
    public static function delete($sqlstatement, array $bind = [])
    {
        static::verifyConnection();

        if (preg_match("/^delete\sfrom\s[\w\d_`]+\swhere\s.+;?$/i", $sqlstatement)) {
            return static::executePrepareQuery($sqlstatement, $bind);
        }

        return false;
    }

    /**
     * Charge le factory Table
     *
     * @param string $tableName le nom de la table
     *
     * @return Table
     */
    public static function table($tableName)
    {
        static::verifyConnection();

        return Table::load($tableName, static::$db);
    }

    /**
     * Insertion des données dans la DB avec la method query
     * ====================== USAGE ========================
     *	$options = [
     *		"query" => [
     *			"table" => "nomdelatable",
     *			"type" => INSERT|SELECT|DELETE|UPDATE,
     *			"data" => [ les informations a mettre ici dépendent de la requête que l'utilisateur veux faire. ]
     *		],
     *		"data" => [ "les données a insérer." ]
     *	];
     * 
     * @param array $options
     * @param bool|false $return
     * @param bool|false $lastInsertId
     * 
     * @throws \ErrorException
     * 
     * @return array|static|\StdClass
     */
    public static function query(array $options, $return = false, $lastInsertId = false)
    {
        static::verifyConnection();

        $sqlStatement = static::makeQuery($options["query"]);
        $pdoStatement = static::$db->prepare($sqlStatement);

        static::bind($pdoStatement, isset($options["data"]) ? $options["data"] : []);

        $pdoStatement->execute();
        static::$errorInfo = $pdoStatement->errorInfo();
        $data = $pdoStatement->fetchAll();

        if ($return == true) {
            if ($lastInsertId == false) {
                $data = empty($data) ? null : Security::sanitaze($data);
            } else {
                $data = static::$db->lastInsertId();
            }
        }

        return $data;
    }

    /**
     * Lancement du debut d'un transaction
     */
    public static function transaction()
    {
        static::verifyConnection();
        static::$db->beginTransaction();
    }

    /**
     * Valider une transaction
     */
    public static function commit()
    {
        static::verifyConnection();
        static::$db->commit();
    }

    /**
     * Annuler une transaction
     */
    public static function rollback()
    {
        static::verifyConnection();
        static::$db->rollBack();
    }

    /**
     * Lance la verification de l'établissement de connection
     * 
     * @throws ConnectionException
     */
    private static function verifyConnection()
    {
        if (! (static::$db instanceof PDO)) {
            static::connection(static::$zone, function($err) {
                if ($err instanceof PDOException) {
                    throw $err;
                }
            });
        }
    }
    /**
     * Récupère l'identifiant de la dernière enregistrement.
     *
     * @param string $name
     * @return int
     */
    public static function lastInsertId($name = null)
    {
        static::verifyConnection();
        return (int) static::$db->lastInsertId($name);
    }

    /**
     * Récupère la dernière erreur sur la l'object PDO
     * 
     * @return DatabaseErrorHandler
     */
    public static function getLastErreur()
    {
        return new DatabaseErrorHandler(static::$errorInfo);
    }

    /**
     * makeQuery, fonction permettant de générer des SQL Statement à la volé.
     *
     * @param array $options, ensemble d'information
     * @param callable $cb = null
     * @return string $query, la SQL Statement résultant
     */
    private static function makeQuery($options, $cb = null)
    {
        /** NOTE:
         *	 | - where
         *	 | - order
         *	 | - limit | take.
         *	 | - grby
         *	 | - join
         *
         *	 Si vous spécifiez un join veillez définir des alias
         *	 $options = [
         *	 	"type" => SELECT,
         * 		"table" => "table as T",
         *	 	"join" => [
         * 			"otherTable" => "otherTable as O",
         *	 		"on" => [
         *	 			"T.id",
         *	 			"O.parentId"
         *	 		]
         *	 	],
         *	 	"where" => "T.id = 1",
         *	 	"order" => ["column", true],
         *	 	"limit" => "1, 5",
         *	 	"grby" => "column"
         *	 ];
         */

        $query = "";
        
        switch ($options['type']) {
            /**
             * Niveau équivalant à un quelconque SQL Statement de type:
             *  _________________
             * | SELECT ? FROM ? |
             *  -----------------
             */
            case self::SELECT:
                /**
                 * Initialisation de variable à usage simple
                 */
                $join  = '';
                $where = '';
                $order = '';
                $limit = '';
                $grby  = '';
                $between = '';

                if (isset($options["join"])) {
                    $join = " INNER JOIN " . $options['join']["otherTable"] . " ON " . implode(" = ", $options['join']['on']);
                }
                /*
                 * Vérification de l'existance d'un clause:
                 *  _______
                 * | WHERE |
                 *  -------
                 */
                if (isset($options['where'])) {
                    $where = " WHERE " . $options['where'];
                }
                /*
                 * Vérification de l'existance d'un clause:
                 *  __________
                 * | ORDER BY |
                 *  ----------
                 */
                if (isset($options['-order'])) {
                    $order = " ORDER BY " . (is_array($options['-order']) ? implode(", ", $options["-order"]) : $options["-order"]) . " DESC";
                } else if (isset($options['+order']) || isset($options['order'])) {
                    $order = " ORDER BY " . (is_array($options['+order']) ? implode(", ", $options["+order"]) : $options["+order"]) . " ASC";
                }

                /*
                 * Vérification de l'existance d'un clause:
                 *  _______
                 * | LIMIT |
                 *  -------
                 */
                if (isset($options['limit']) || isset($options["take"])) {
                    if (isset($options['limit'])) {
                        $param = $options['limit'];
                    } else {
                        $param = $options['take'];
                    }
                    $param = is_array($param) ? implode(", ", array_map(function($v){
                        return (int) $v;
                    }, $param)) : $param;
                    $limit = " LIMIT " . $param;
                }

                /**
                 * Vérification de l'existance d'un clause:
                 *  ----------
                 * | GROUP BY |
                 *  ----------
                 */
                
                if (isset($options["grby"])) {
                    $grby = " GROUP BY " . $options['grby'];
                    if (isset($options["having"])) {
                        $grby .= " HAVING " .$options["having"];
                    }
                }

                if (isset($options["data"])) {

                    if (is_array($options["data"])) {
                        $data = implode(", ", $options['data']);
                    } else {
                        $data = $options['data'];
                    }

                } else {
                    $data = "*";
                }
                /**
                 * Vérification de l'existance d'un clause:
                 *  ----------
                 * | BETWEEN  |
                 *  ----------
                 */

                if (isset($options["-between"])) {
                    $between = $options[0] . " NOT BETWEEN " . implode(" AND ", $options["between"][1]);
                } else if (isset($options["between"])) {
                    $between = $options[0] . " BETWEEN " . implode(" AND ", $options["between"][1]);
                }

                /**
                 * Edition de la SQL Statement facultatif.
                 * construction de la SQL Statement finale.
                 */
                $query = "SELECT " . $data . " FROM " . $options['table'] . $join . $where . ($where !== "" ? $between : "") . $order . $limit . $grby;
                break;
            /**
             * Niveau équivalant à un quelconque
             * SQL Statement de type:
             *  _____________
             * | INSERT INTO |
             *  -------------
             */
            case self::INSERT:
                /**
                 * Sécurisation de donnée.
                 */
                $field = self::rangeField($options['data']);
                /**
                 * Edition de la SQL Statement facultatif.
                 */
                $query = "INSERT INTO " . $options['table'] . " SET " . $field;
                break;
            /**
             * Niveau équivalant à un quelconque
             * SQL Statement de type:
             *  ________
             * | UPDATE |
             *  --------
             */
            case self::UPDATE:
                /**
                 * Sécurisation de donnée.
                 */
                $field = self::rangeField($options['data']);
                /**
                 * Edition de la SQL Statement facultatif.
                 */
                $query = "UPDATE " . $options['table'] . " SET " . $field . " WHERE " . $options['where'];
                break;
            /**
             * Niveau équivalant à un quelconque
             * SQL Statement de type:
             *  _____________
             * | DELETE FROM |
             *  ------------
             */
            case self::DELETE:
                /**
                 * Edition de la SQL Statement facultatif.
                 */
                $query = "DELETE FROM " . implode(", ", $options['table']) . " WHERE " . $options['where'];
                break;
        }
        /**
         * Vérification de l'existance de la fonction de callback
         */
        if ($cb !== null) {
            /** NOTE:
             * Execution de la fonction de rappel,
             * qui récupère une erreur ou la query
             * pour évantuel vérification
             */
            call_user_func($cb, isset($query) ?: $query);
        }

        return $query;
    }

    /**
     * Execute Les request de type delete insert update
     *
     * @param $sqlstatement
     * @param array $bind
     * @return mixed
     */
    private static function executePrepareQuery($sqlstatement, array $bind = [])
    {
        $pdostatement = static::$db->prepare($sqlstatement);
        
        static::bind($pdostatement, $bind);
        $pdostatement->execute();
        static::$errorInfo = $pdostatement->errorInfo();

        $r = $pdostatement->rowCount();
        $pdostatement->closeCursor();

        return $r;
    }

    /**
     * pdo, retourne l'instance de la connection.
     * 
     * @return PDO
     */
    public static function getPdo()
    {
        static::verifyConnection();
        return static::$db;
    }

    /**
     * modifie l'instance de PDO
     *
     * @param PDO $pdo
     */
    public static function setPdo(PDO $pdo)
    {
        static::$db = $pdo;
    }

    /**
     * __call
     *
     * @param $method
     * @param array $arguments
     *
     * @throws DatabaseException
     *
     * @return mixed
     */
    public function __call($method, array $arguments)
    {
        if (method_exists(static::class, $method)) {
            return call_user_func_array([__CLASS__, $method], $arguments);
        }

        throw new DatabaseException("$method n'est pas une methode.", E_USER_ERROR);
    }
}
