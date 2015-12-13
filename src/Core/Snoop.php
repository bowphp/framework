<?php

/**
 * @author diagnostic sarl, <info@diagnostic-ci.com>
 * create and maintener by diagnostic developpers teams:
 * - Etchien Boa
 * - Dakia Franck
 * - Zokora Elvis
 * @+- 10/06/2015 fast web app building
 * @package Snoope
 */

namespace System\Core;

use System\Util\Logger;
use System\Database\DB;
use System\Util\Util;
use System\Http\Response;
use System\Http\Request;

class Snoop
{
	/***
	 * Liste des constances
	 * d'execution de Requete
	 * SQL. Pour le system de
	 * de base de donnee ultra
	 * minimalise de snoop.
	 */
	const SELECT = 1;
	const UPDATE = 2;
	const DELETE = 3;
	const INSERT = 4;

	// Collecteur de route.
	private static $routes = [];
	// Definition de contrainte sur un route.
	private $with = [];
	// Branchement global sur un liste de route
	private $branch = "";
	// Represente le chemin vers la vue.
	private $views = null;
	/**
	 * Systeme de template
	 *
	 * @var string|null
	 */
	private $engine = null;
	/**
	 * Represente de la racine de l'application
	 *
	 * @var string
	 */
	private $root = "";
	/**
	 * Epresente le dossier public
	 *
	 * @var string
	 */
	private $public = "";
	/**
	 * Enregistre la route courante
	 *
	 * @var string
	 */
	private $currentRoot = "";
	private $error404 = null;
	// Patter Singleton
	private static $inst = null;
	private static $mail = null;
	private static $appname = null;
	private static $loglevel = "dev";
	private $logFileName = "";
	private $tokenCsrfExpirateTime;
	private $namespace = "App\Http\MyController";
	private $middlewareNameSpace = "App\Http\Middleware";
	/**
	 * Configuration de date en francais.
	 */
	private static $angMounth = [
		"Jan"  => "Jan", "Fév"  => "Feb",
		"Mars" => "Mar", "Avr"  => "Apr",
		"Mai"  => "Mai", "Juin" => "Jun",
		"Juil" => "Jul", "Août" => "Aug",
		"Sept" => "Sep", "Oct"  => "Oct",
		"Nov"  => "Nov", "Déc"  => "Dec"
	];
	private static $month = [
		"Jan"  => "Janvier", "Fév"  => "Fevrier",
		"Mars" => "Mars", "Avr"  => "Avril",
		"Mai"  => "Mai", "Juin" => "Juin",
		"Juil" => "Juillet", "Août" => "Août",
		"Sept" => "Septembre", "Oct" => "Octobre",
		"Nov"  => "Novembre", "Déc" => "Décembre"
	];
	/**
	 * Private construction
	 */
	private function __construct($config)
	{
        if (isset($config->timezone)) {
            Util::settimezone($config->timezone);
        }
		self::$appname = $config->appname;
		$this->logDirecotoryName = $config->logDirecotoryName;
        self::$loglevel = isset($config->loglevel) ? $config->loglevel : self::$loglevel;
		Logger::run();
	}
	/**
	 * Private __clone
	 */
	private function __clone(){}

	/**
	 * Pattern Singleton.
	 * @return self
	 */
	public static function loader($config)
	{
		if (self::$inst === null) {
			self::$inst = new self($config);
		}
		return self::$inst;
	}

	/**
	 * Pattern singleton et factory.
	 * @param boolean $smtp=false
	 * @return Mail
	 */
	public static function mailFactory($smtp = false)
	{
		if (self::$mail === null) {
			if ($smtp === true) {
				self::$mail = SmtpMail::load();
			} else {
				self::$mail = Mail::load();
			}
		}
		return self::$mail;
	}
	/**
	 * mount, ajout un branchement.
	 * @param string $branchName
	 * @param callable|null $middelware
	 * @return self
	 */
	public function mount($branchName, $middelware = null)
	{
		if ($middelware !== null) {
			call_user_func($middelware, [$this->request(), $this->response()]);
		}
		$this->branch .= $branchName;
		return $this;
	}

	/**
	 * unmount, détruit le branchement en cour.
	 * @return self
	 */
	public function unmount()
	{
		$this->branch = "";
		return $this;
	}

	/**
	 * get, route de type GET
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	public function get($path, $cb = null)
	{
		if ($cb == null) {
			$prop = $path;
			if (property_exists($this, $prop)) {
				return $this->$prop;
			}
		}
		$this->currentRoot = $this->branch . $path;
		return $this->routeLoader("GET", $this->currentRoot, $cb);
	}

	/**
	 * any, route de tout type GET et POST
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	public function any($path, $cb)
	{
		$this->post($path, $cb)
		->delete($path, $cb)
		->put($path, $cb)
		->update($path, $cb)
		->get($path, $cb);
		return $this;
	}

	/**
	 * any, route de tout type DELETE
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	public function delete($path, $cb)
	{
		return $this->addHttpVerbe("_DELETE", $path, $cb);
	}

	/**
	 * any, route de tout type UPDATE
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	public function update($path, $cb)
	{
		return $this->addHttpVerbe("_UPDATE", $path, $cb);
	}

	/**
	 * any, route de tout type PUT
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	public function put($path, $cb)
	{
		return $this->addHttpVerbe("_PUT", $path, $cb);
	}

	/**
	 * any, route de tout type PUT
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	public function head($path, $cb)
	{
		return $this->addHttpVerbe("_HEAD", $path, $cb);
	}

	/**
	 * to404, Charge le fichier 404 en cas de non
	 * validite de la requete
	 * @param callable $cb
	 * @return self
	 */
	public function to404($cb)
	{
		$this->error404 = $cb;
		return $this;
	}

	/**
	 * any, route de tout type PUT
	 * @param callable $cb
	 * @return self
	 */
	public function match($match, $middleware, $cb)
	{
		if (in_array($this->getMethod(), $match)) {

		}
		$this->error404 = $cb;
		return $this;
	}

	/**
	 * addHttpVerbe, permet d'ajout les autres verbes https
	 * PUT, DELETE, UPDATE, HEAD
	 * @param string $method
	 * @param string $path
	 * @param callable $cb
	 * @return self
	 */
	private function addHttpVerbe($method, $path, $cb)
	{
		if ($this->isBodyKey("method")) {
			if ($this->body("method") === $method) {
				$this->routeLoader($this->getMethod(), $this->branch . $path, $cb);
			}
		}
		return $this;
	}

	/**
	 * post, route de type POST
	 *
	 * @param string $path
	 * @param callable $cb
	 * @return \System\Snoop
	 */
	public function post($path, $cb)
	{
		if ($this->isBodyKey("method")) {
			return $this;
		}
		$this->currentRoot = $this->branch . $path;
		return $this->routeLoader("POST", $this->currentRoot, $cb);
	}

	/**
	 * routeLoader, lance le chargement d'une route.
	 * @param string $method
	 * @param string $path
	 * @param callable $cb
	 * @return \System\Snoop
	 */
	private function routeLoader($method, $path, $cb)
	{
		if (is_array($cb)) {
			$collection = $cb;
			if (array_key_exists("middleware", $cb)) {
				$middleware = $collection["middelware"];
			} else if (array_key_exists("next", $cb)) {
				$cb = $collection["next"];
			} else if (array_key_exists("as", $cb)) {
				$route_name = $collection["as"];
			} else {
				$cb = $collection[0];
			}
		}
		self::$routes[$method][] = new Route($path, $cb, $this->with);
		$this->with = [];
		return $this;
	}

	/**
	 * Lance une personnalistaion de route.
	 * @param array $otherRule
	 * @return \System\Snoop
	 */
	public function with(array $otherRule)
	{
		$this->with = array_merge($this->with, $otherRule);
		return $this;
	}

	/**
	 * Lanceur de l'application
	 */
	public function run()
	{
		$this->response()->setHeader("X-Powered-By", "Snoop Framework");
		$error = true;
		if (isset(self::$routes[$this->request()->method()])) {
			foreach (self::$routes[$this->request()->method()] as $route) {
				if ($route->match($this->request()->uri($this->root))) {
					$route->call($this->request(), $this->response());
					$error = false;
				}
			}
		} else {
			$error = false;
		}

		if ($error) {
			$this->response()->setResponseCode(404);
			if ($this->error404 !== null && is_callable($this->error404)) {
				call_user_func($this->error404);
			}
			self::log("[404] route -" . $this->request()->uri() . "- non definie");
		}

	}

	/**
	 * middelware launcher
	 * @param callable $middelware.
	 * @param callable $cb=null
	 * @param mixed $me=null
	 * @return mixed $r
	 */
	public function middelware($middelware, $cb = null, $me = null)
	{
		$middelware = str_replace(".", DIRECTORY_SEPARATOR , $middelware);
		$r = require $middelware . ".php" ;
		if ($cb !== null) {
			return call_user_func($cb, isset($r) ? $r : false);
		}
		return $r;
	}

	/**
	 * Kill process
	 *
	 * @param string $message=""
	 * @param int|bool $status
	 * @param bool $log=false
	 * @return void
	 */
	public function kill($message = "", $status = 200, $log = false)
	{
		if (is_bool($status) && $status == true) {
			$log = $status;
		} else {
			$this->response()->setResponseCode($status);
		}
		if (!is_string($message)) {
			$message = null;
		}
		if ($log) {
			$this->log($message);
		} else {
			echo $message;
		}
		die();
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
		 * 		"table" => "table",
		 *	 	"join" => [
		 * 			"otherTable" => "otherTable",
		 *	 		"on" => [
		 *	 			"T.id",
		 *	 			"O.parentId"
		 *	 		]
		 *	 	],
		 *	 	"where" => "R.r_num = " . $currentRegister,
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
				 *Vérification de l'existance d'un clause:
				 * __________
				 *| ORDER BY |
				 * ----------
				 */
				if (isset($options['-order'])) {
					$order = " ORDER BY " . (is_array($options['-order']) ? implode(", ", $options["-order"]) : $options["-order"]) . " DESC";
				} else if (isset($options['+order'])) {
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
					$param = is_array($param) ? implode(", ", $param) : $param;
					$limit = " LIMIT " . $param;
				}

				/**
				 * Vérification de l'existance d'un clause:
				 * ----------
				 *| GROUP BY |
				 * ----------
				 */
				if (isset($options->grby)) {
					$grby = " GROUP BY " . $options['grby'];
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

				if (isset($options["between"])) {
					$between = $options[0] . " NOT BETWEEN " . implode(" AND ", $options["between"]);
				} else if (isset($options["-between"])) {
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
			call_user_func($cb, isset($query) ? $query : E_ERROR);
		}
		return $query;
	}

	/**
	 * bindValueAndExecuteQuery, fonction permettant d'executer des SQL Statement
	 *
	 * @param array $data
	 * @param \PDOStatement $pdoStatement
	 * @param bool $retournData
	 * @return \StdClass $resultat
	 */
	private static function bindValueAndExecuteQuery($data, $pdoStatement, $retournData = false)
	{
		foreach ($data as $key => $value) {
			if ($value === "NULL") continue;
			$param = \PDO::PARAM_INT;
			if (preg_match("/[a-zA-Z_-]+/", $value)) {
				/**
				 * SÉCURIATION DES DONNÉS
				 *- Injection SQL
				 *- XSS
				 */
				$param = \PDO::PARAM_STR;
				$value = addslashes($value);
				$value = trim($value);
				$value = htmlspecialchars($value);
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
		/**
		 * Récupération de l'état de l'execution.
		 */
		$status = $pdoStatement->execute();
		/**
		 * Initilisation d'un object StdClass.
		 */
		$resultat = new \StdClass;
		/**
		 * On vérifie si la récupération de donnée est active.
		 */
		if ($retournData === true) {
			/**
			 * Récupération des données.
			 */
			if ($pdoStatement->rowCount() == 1) {
				$fetch = "fetch";
			} else {
				$fetch = "fetchAll";
			}
			$resultat->data = $pdoStatement->$fetch();
		}
		/**
		 * Récupération d'une erreur quelconque.
		 */
		$resultat->error = !$status;
		return $resultat;
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

	/**
	 * getPdoError, fonction permettant d'obtenir des informations sur une erreur PDO
	 *
	 * @param \PDO|\PDOStatement $pdoStatement
	 */
	private static function getPdoError($pdoStatement)
	{
		$error = $pdoStatement->errorInfo();
		$errorCode = current($error);
		$errorMessage = end($error);
		$content =  $errorCode . " : " . $errorMessage;

		if (self::$logLevel == "dev") {
			echo '<div style="margin: auto; width: 500px; text-align: center; font-size: 16px; color: red; border: 5px solid tomato; border-radius: 5px; padding: 10px;">';
			echo $content;
			echo '</div>';
		} else {
			self::log($content);
		}

		self::kill();
	}


	/**
	 * Permettant de convertie des chiffres en letter
	 * @param string $nombre
	 * @return string
	 */
	public function convertDate($nombre)
	{
		$nombre = (int) $nombre;
		if ($nombre === 0) {
			return "zéro";
		}
		/**
		 * Definition des elements de convertion.
		 */
		$nombreEnLettre = [
			"unite" => [
				null, "un", "deux", "trois", "quatre",
				"cinq", "six", "sept", "huit", "neuf",
				"dix", "onze", "douze", "treize", "quartorze",
				"quinze", "seize", "dix-sept", "dix-huit", "dix-neuf"
			],
			"ten" => [
				null, "dix", "vingt", "trente", "quarente", "cinquante",
				"soixante", "soixante",  "quatre-vingt", "quatre-vingt"
			]
		];
		/**
		 * Calcule des:
		 * - Unité
		 * - Dixaine
		 * - Centaine
		 * - Millieme
		 */
		$unite = $nombre % 10;
		$dixaine = ($nombre % 100 - $unite) / 10;
		$cent = ($nombre % 1000 - $nombre % 100) / 100;
		$millieme = ($nombre % 10000 - $nombre % 1000) / 1000;
		/**
		 * Calcule des unites
		 */
		$unitsOut = ($unite === 1 && $dixaine > 0 && $dixaine !== 8 ? 'et-' : '') . $nombreEnLettre['unite'][$unite];

		$tensOut = "";
		$centsOut = "";
		/**
		 * Calcule des dixaines
		 */
		if ($dixaine === 1 && $unite > 0) {
			$tensOut = $nombreEnLettre["unite"][10 + $unite];
			$unitsOut = "";
		} else if ($dixaine === 7 || $dixaine === 9) {
			$tensOut = $nombreEnLettre["ten"][$dixaine] . '-' . ($dixaine === 7 && $unite === 1 ? "et-" : "") . $nombreEnLettre["unite"][10 + $unite];
			$unitsOut = "";
		} else {
			$tensOut = $nombreEnLettre["ten"][$dixaine];
		}
		/**
		 * Calcule des cemtaines
		 */
		$tensOut .= ($unite === 0 && $dixaine === 8 ? "s": "");
		$centsOut = ($cent > 1 ? $nombreEnLettre["unite"][(int)$cent].' ' : '').($cent > 0 ? 'cent' : '').($cent > 1 && $dixaine == 0 && $unite == 0 ? '' : '');
		$tmp = $centsOut.($centsOut && $tensOut ? ' ': '').$tensOut.(($centsOut && $unitsOut) || ($tensOut && $unitsOut) ? '-': '').$unitsOut;
		/**
		 * Retourne avec les millieme associer.
		 */
		return ($millieme === 1 ? "mil":($millieme > 1 ? $nombreEnLettre["unite"][(int) $millieme]." mil" : "")).($millieme ? " ".$tmp : $tmp);
	}

	/**
	 * makothereSimpleValideDate
	 * @param string $str
	 * @return string
	 */
	public function makeSimpleValideDate($str)
	{
		$mount = explode(" ", $str);
		$str = $mount[0] . " " . self::$angMounth[$mount[1]] . " " . $mount[2];
		return date("Y-m-d", strtotime($str));
	}

	/**
	 * permettant de convertir mois en lettre.
	 * @param  string | integer $value
	 * @return string
	 */
	public function getMonth($value)
	{
		if (!empty($value)) {
			if (is_string($value)) {
				//definition du tableau  composants les mois  avec key en string
				if (strlen($value) == 3) {
					$value = ucfirst($value);
					$month = self::$month;
				} else {
					return null;
				}
			} else {
				$value = (int) $value;

				//definition du tableau  composants les mois
				if ($value > 0 && $value <= 12) {
					$value -= 1;
				} else {
					return  null;
				}
				$month = array_values(self::$month);
			}
			return $month[$value];
		}
		return $this;
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
		if (self::$db === null) {
			throw new \ErrorException(__METHOD__ . "(): La connection n'est pas initialiser.<br/>Snoop::connection('default'[,function])");
		}
		// Contruction de la requete sql
		$sqlStatement = self::makeQuery($options["query"]);
		$pdoStatement = self::$db->prepare($sqlStatement);
		$r = self::bindValueAndExecuteQuery(isset($options["data"]) ? $options["data"] : [], $pdoStatement, true);

		if ($r->error) {
			self::getPdoError($pdoStatement);
		}
		if ($return == true) {
			if ($lastInsertId == false) {
				return empty($r->data) ? null : Security::sanitaze($r->data);
			}
			return self::$db->lastInsertId();
		}
		return $this;
	}

	/**
	 * Set, permet de rédéfinir la configuartion
	 * @param string $key
	 * @param string $value
	 * @throws \InvalidArgumentException
	 */
	public function set($key, $value)
	{
		if (in_array($key, ["views", "engine", "public", "root"])) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		} else {
			throw new \InvalidArgumentException("Le premier argument n'est pas un argument de configuration");
		}
	}

	/**
	 * Lance un var_dump sur les variables passées en parametre.
	 * @throws \InvalidArgumentException
	 */
	public function debug()
	{
		if (func_num_args() == 0) {
			throw new \InvalidArgumentException("Vous devez donner un paramtre à la function", 1);
		}
		ob_start();
		foreach (func_get_args() as $key => $value) {
			var_dump($value);
			echo "\n";
		}
		$content = ob_get_clean();
		$content = preg_replace("~\s?\{\n\s?\}~i", " is empty", $content);
		$content = preg_replace("~(string|int|object|stdclass|bool|double|float|array)~i", "<span style=\"color: rgba(255, 0, 0, 0.5); font-style: italic\">&lt;$1&gt;</span>", $content);
		$content = preg_replace('~\((\d+)\)~im', "<span style=\"color: #498\">(len=$1)</span>", $content);
		$content = preg_replace('~\s(".+")~im', "<span style=\"color: #458\"> value($1)</span>", $content);
		$content = preg_replace("~(=>)(\n\s+?)+~im", "<span style=\"color: #754\"> is</span>", $content);
		$content = preg_replace("~(is</span>)\s+~im", "$1 ", $content);
		$content = preg_replace("~\[(.+)\]~im", "<span style=\"color:#666\"><span style=\"color: red\">key:</span>$1<span style=\"color: red\"></span></span>", $content);
		$content = "<pre><tt><div style=\"font-family: monaco, courier; font-size: 13px\">$content</div></tt></pre>";
		$this->kill($content);
	}

	/**
	 * systeme de débugage avec message d'info
	 * @param string $message
	 * @param callable $cb=null
	 * @return void
	 */
	public function it($message, $cb = null)
	{
		echo "<h2>{$message}</h2>";
		if (is_callable($cb)) {
			call_user_func_array($cb, [$this]);
		} else {
			$this->debug(array_slice(func_get_args(), 1, func_num_args()));
		}
		$this->kill();
	}

	/**
	 * GetRoot, retourne la route principale.
	 * @return string
	 */
	public function getRoot()
	{
		return $this->root;
	}

	/**
	 * GetPublicPath, retourne lresponsea route definir pour dossier public.
	 * @return string
	 */
	public function getPublicPath()
	{
		return $this->public;
	}

	/**
	 * body, retourne les informations du POST ou une seule si un clé est
	 * passée paramètre
	 * @param string $key=null
	 * @return array
	 */
	public function body($key = null)
	{

		if ($key !== null) {
			return $this->isBodyKey($key) ? $_POST[$key] : false;
		}
		return $_POST;
	}

	/**
	 * isBodyKey, vérifie si de Snoop::body contient la clé definie.
	 * @param mixed $key
	 * @return mixed $key
	 */
	public function isBodyKey($key)
	{
		return isset($_POST[$key]) && !empty($_POST[$key]);
	}

	/**
	 * bodyIsEmpty, vérifie si le tableau $_POST est vide.
	 *	@return boolean
	 */
	public function bodyIsEmpty()
	{
		return empty($_POST);
	}

	/**
	 * Param, retourne les informations du GET ou une seule si un clé est
	 * passée paramètre
	 * @param string $key=null
	 * @return array
	 */
	public function param($key = null)
	{
		if ($key !== null) {
			return $this->isParamKey($key) ? $_GET[$key] : false;
		}
		return $_GET;
	}

	/**
	 * isParamKey, vérifie si de Snoop::param contient la cle definie.
	 * @param string|int $key
	 * @return mixed
	 */
	public function isParamKey($key)
	{
		return isset($_GET[$key]) && !empty($key);
	}

	/**
	 * paramIsEmpty, vérifie si le tableau $_GET est vide.
	 *	@return boolean
	 */
	public function paramIsEmpty()
	{
		return empty($_GET);
	}

	/**
	 * files, retourne les informations du $_FILES
	 * @param string|null $key
	 * @return mixed
	 */
	public function files($key = null)
	{
		if ($key !== null) {
			return isset($_FILES[$key]) ? (object) $_FILES[$key] : false;
		}
		return $_FILES;
	}

	/**
	 * isParamKey, vérifie si Snoop::files contient la clé définie.
	 * @param string|int $key
	 * @return mixed
	 */
	public function isFilesKey($key)
	{
		return isset($_FILES[$key]) && !empty($_FILES[$key]);
	}

	/**
	 * filesIsEmpty, vérifie si le tableau $_FILES est vide.
	 *	@return boolean
	 */
	public function filesIsEmpty()
	{
		return empty($_FILES);
	}

	/**
	 * currentRoot, retourne la route courante
	 * @return string
	 */
	public function currentRoot()
	{
		return $this->currentRoot;
	}

	/**
	 * Res, retourne une instance de Response
	 * @return \System\Response\Response
	 */
	private function response()
	{
		return Response::load($this);
	}

	/**
	 * Req, retourne une instance de Request
	 * @return \System\Response\Request
	 */
	private function request()
	{
		return Request::load($this);
	}

	/**
	 * Logeur d'erreur.
	 * @param string $message
	 */
	private function log($message)
	{
		$f_log = fopen($this->logDirecotoryName . "/error.log", "a+");
		if ($f_log != null) {
			fprintf($f_log, "[%s] - %s:%d: %s\n", date("Y-m-d H:i:s"), $_SERVER['REMOTE_ADDR'], $_SERVER["REMOTE_PORT"], $message);
			fclose($f_log);
		}
	}

}
