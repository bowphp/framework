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
     * Information sur les erreurs de pdoStement
     * 
     * @var array
     */
    private static $currentPdoStementErrorInfo = [];
    /**
     * Information sur les erreurs de pdo
     * 
     * @var array
     */
    private static $currentPdoErrorInfo = [];
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
     * @var array
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
        }
        return static::$config = (object) $config;
    }

    /**
     * @return Database
     */
    public static function takeInstance()
    {
        return static::$instance;
    }
    /**
     * connection, lance la connection sur la DB
     *
     * @param null $option
     * @param null $cb
     * @return null|Database
     */
    public static function connection($option = null, $cb = null)
    {
        if (static::$db instanceof PDO) {
            return null;
        }

        if ($option !== null) {
            if (is_string($option)) {
                static::$zone = $option;
            } else if (is_callable($option)) {
                static::$zone = "default";
                $cb = $option;
            }
        } else {
            static::$zone = "default";
        }

        /**
         * Essaie de la connection
         */
        $t = static::$config;

        if (! $t instanceof StdClass) {
            Util::launchCallback($cb, [new ConnectionException("Le fichier database.php est mal configurer")]);
        }

        $c = isset($t->connections[static::$zone]) ? $t->connections[static::$zone] : null;

        if (is_null($c)) {
            Util::launchCallback($cb, [new ConnectionException("La clé '". static::$zone . "' n'est pas définir dans l'entre database.php")]);
        }

        $db = null;

        try {
            // Construction de la dsn
            $username = null;
            $password = null;

            // Configuration suppelement coté PDO
            $pdoPostConfiguation = [
                PDO::ATTR_DEFAULT_FETCH_MODE => $t->fetch
            ];

            switch($c["scheme"]) {
                case "mysql":
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
	 * @param string $enterKey
	 * @param callable $cb
	 * @return void
	 */
	public static function switchTo($enterKey, $cb = null)
	{
        static::verifyConnection();

		if (!is_string($enterKey)) {
        	Util::launchCallback($cb, [new InvalidArgumentException("paramètre invalide")]);
        } else {
            if($enterKey !== static::$zone) {
                static::$db = null;
                static::connection($enterKey, $cb);
            } else {
                Util::launchCallback($cb, static::takeInstance());
            }
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

            static::$currentPdoStementErrorInfo = $pdostatement->errorInfo();
            static::$currentPdoErrorInfo = static::$db->errorInfo();

            return Security::sanitaze($pdostatement->fetchAll());
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

        if (preg_match("/^insert\sinto\s[\w\d_-`]+\s?(\(.+\)?\s(values\s?\(.+\),?){1,}|\s?set\s(.+)+);?$/i", $sqlstatement)) {

            $r = 0;
            $is_2_m_array = true;

            if (count($bind) > 0) {
                foreach ($bind as $key => $value) {
                    if (is_array($value)) {
                        $r += static::executePrepareQuery($sqlstatement, $value);
                    } else {
                        $is_2_m_array = false;
                    }
                }

                if (!$is_2_m_array) {
                    $r += static::executePrepareQuery($sqlstatement, $bind);
                }

            } else {
                $r = static::executePrepareQuery($sqlstatement, $bind);
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
            static::$currentPdoErrorInfo = static::$db->errorInfo();
            
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
     * @param $tableName
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
     * Lance la verification de l'établissement de connection
     * 
     * @throws ConnectionException
     * @var void
     */
    private static function verifyConnection()
    {
        if (! (static::$db instanceof PDO)) {
            static::connection(function($err) {
                if ($err instanceof PDOException) {
                    throw $err;
                }
            });
        }
    }
    /**
     * Récupère l'identifiant de la dernière enregistrement.
     * 
     * @return int
     */
    public static function lastInsertId()
    {
        static::verifyConnection();
        return (int) static::$db->lastInsertId();
    }

    /**
     * Récupère la dernière erreur sur la l'object PDO
     * 
     * @return array
     */
    public static function getLastErreur()
    {
        return new DatabaseErrorHandler(static::$currentPdoStementErrorInfo);
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
                 * _______
                 *| WHERE |
                 * -------
                 */
                if (isset($options['where'])) {
                    $where = " WHERE " . $options['where'];
                }
                /*
                 * Vérification de l'existance d'un clause:
                 * __________
                 *| ORDER BY |
                 * ----------
                 */
                if (isset($options['-order'])) {
                    $order = " ORDER BY " . (is_array($options['-order']) ? implode(", ", $options["-order"]) : $options["-order"]) . " DESC";
                } else if (isset($options['+order']) || isset($options['order'])) {
                    $order = " ORDER BY " . (is_array($options['+order']) ? implode(", ", $options["+order"]) : $options["+order"]) . " ASC";
                }

                /*
                 * Vérification de l'existance d'un clause:
                 * _______
                 *| LIMIT |
                 * -------
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
                 * ----------
                 *| GROUP BY |
                 * ----------
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
                 * ----------
                 *| BETWEEN  |
                 * ----------
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
             * _____________
             *| INSERT INTO |
             * -------------
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
             * ________
             *| UPDATE |
             * --------
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
             * _____________
             *| DELETE FROM |
             * -------------
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

        static::$currentPdoStementErrorInfo = $pdostatement->errorInfo();
        static::$currentPdoErrorInfo = static::$db->errorInfo();

        $r = $pdostatement->rowCount();
        $pdostatement->closeCursor();

        return $r;
    }

    /**
     * pdo, retourne l'instance de la connection.
     * 
     * @return PDO
     */
    public static function pdo()
    {
        static::verifyConnection();
        return static::$db;
    }

    /**
     * @param $method
     * @param array $arguments
     * @throws DatabaseException
     * @return mixed
     */
    public function __call($method, array $arguments)
    {
        if (method_exists(static::class, $method)) {
            return call_user_func_array([__CLASS__, $method], $arguments);
        }

        throw new DatabaseException("$method not found", E_USER_ERROR);
    }
}
