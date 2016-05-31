<?php
namespace Bow\Http;

use Jade\Jade;
use ErrorException;
use Bow\Support\Str;
use Bow\Support\Session;
use Bow\Support\Security;
use Bow\Core\AppConfiguration;
use Bow\Exception\ViewException;
use Bow\Exception\ResponseException;

/**
 * Class Response
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Http
 */
class Response
{
	/**
     * Singleton
     * @var self
     */
    private static $instance = null;

	/**
	 * Liste de code http valide pour l'application
	 * Sauf que l'utilisateur poura lui même rédéfinir
	 * ces codes s'il utilise la fonction `header` de php
	 */
	private static $header = [
		200 => "OK",
		201 => "Created",
		202 => "Accepted",
		204 => "No Content",
		205 => "Reset Content",
		206 => "Partial Content",
		300 => "Multipe Choices",
		301 => "Moved Permanently",
		302 => "Found",
		303 => "See Other",
		304 => "Not Modified",
		305 => "Use Proxy",
		307 => "Temporary Redirect",
		400 => "Bad Request",
		401 => "Unauthorized",
		402 => "Payment Required",
		403 => "Forbidden",
		404 => "Not Found",
		405 => "Method Not Allowed",
		406 => "Not Acceptable",
		407 => "Proxy Authentication",
		408 => "Request Time Out",
		409 => "Conflict",
		410 => "Gone",
		411 => "Length Required",
		412 => "Precondition Failed",
		413 => "Payload Too Large",
		414 => "URI Too Long",
		415 => "Unsupport Media",
		416 => "Range Not Statisfiable",
		417 => "Expectation Failed",
		500 => "Internal Server Error",
		501	=> "Not Implemented",
		502	=> "Bad Gateway",
		503 => "Service Unavailable",
		504	=> "Gateway Timeout",
		505	=> "HTTP Version Not Supported",
	];

	/**
     * Instance de l'application
     * @var AppConfiguration
     */
    private $config;

	/**
	 * Constructeur de l'application
	 *
	 * @param AppConfiguration $appConfig
	 */
    private function __construct(AppConfiguration $appConfig)
    {
        $this->config = $appConfig;
    }

    /**
     * Singleton loader
     * 
     * @param AppConfiguration $appConfig
     * @return self
     */
    public static function configure(AppConfiguration $appConfig)
    {
        if (self::$instance === null) {
            self::$instance = new self($appConfig);
        }

        return self::$instance;
    }

	/**
	 * Retourne l'instance de Response
	 *
	 * @return Response
	 */
	public static function takeInstance()
	{
		return self::$instance;
	}

	/**
	 * Modifie les entêtes http
	 *
	 * @param string $key 	Le nom de l'entête
	 * @param string $value La nouvelle valeur a assigne à l'entête
	 * @return self
	 */
	public function set($key, $value)
	{
		header("$key: $value");

		return $this;
	}
    
    /**
     * redirect, permet de lancer une redirection vers l'url passé en paramêtre
     *
     * @param string|array $path L'url de rédirection
	 * Si $path est un tableau :
	 * 	$url = [
	 * 		"url" => "//"
	 * 		"?" => [
	 * 			"name" => "dakia",
	 * 			"lastname" => "franck",
	 * 			"id" => "1",
	 * 		],
	 * 		"$" => "hello"
	 * ];
	 *
     */
    public function redirect($path)
    {
		$url = "/";

		if (is_string($path)) {
			$url = $path;
		} else {
			$url = $path["url"];

			if (isset($path["?"])) {
				$url .= "?";
				$i = 0;
				foreach($path["?"] as $key => $value) {
					if ($i > 0) {
						$url .= "&";
					}
					$url .= $key . "=" . $value;
					$i++;
				}
			}

			if (isset($path["#"])) {
				$url .= "#" . $path["#"];
			}
		}

		header("Location: " . $url, true, 301);
		echo '<a href="' . $url . '" >' . self::$header[301] . '</a>';

		die();
    }

    /**
     * redirectTo404, rédirige vers 404
     * @return self
     */
    public function redirectTo404()
    {
        $this->code(404);
        return $this;
    }

	/**
	 * Modifie les entétes http
	 * 
	 * @param int  $code 	 Le code de la réponse HTTP
	 * @param bool $override Permet de remplacer l'entête ecrite précédement quand la valeur est a "true"
	 * @return bool|void
	 */
	public function code($code, $override = false)
	{
		$r = true;

		if (in_array((int) $code, array_keys(self::$header), true)) {
			header("HTTP/1.1 $code " . self::$header[$code], $override, $code);
		} else {
			$r = false;
		}

		return $r;
	}

	/**
	 * Réponse de type JSON
	 *
	 * @param mixed $data Les données à transformer en JSON
	 * @param int 	$code Le code de la réponse HTTP
	 * @param bool 	$end  Quand est à true il termine le processus
	 */
	public function json($data, $code = 200, $end = false)
	{
		if (is_bool($code)) {
            $end = $code;
            $code = 200;
        };

		$this->set("Content-Type", "application/json; charset=UTF-8");
		$this->code($code);
		$this->send(json_encode($data), $end);
	}

	/**
	 * sendFile, require $filename
	 * 
	 * @param string $filename
	 * @param array $bind
	 * @throws ViewException
	 * @return mixed
	 */
	public function sendFile($filename, $bind = [])
	{
		$filename = preg_replace("/@|#|\./", "/", $filename);

		if ($this->config->getViewpath() !== null) {
			$tmp = $this->config->getViewpath() ."/". $filename . ".php";
			if (!file_exists($tmp)) {
				$filename = $this->config->getViewpath() ."/". $filename . ".html";			
			} else {
				$filename = $tmp;
			}
		}

		if (!file_exists($filename)) {
			throw new ViewException("La vue $filename n'exist pas.", E_ERROR);
		}

 		extract($bind);
		// Rendu du fichier demandé.

		return require $filename;
	}

	/**
	 * render, lance le rendu utilisant le template définir <<mustache|twig|jade>>
	 *
	 * @param string $filename
	 * @param array $bind
	 * @param integer $code=200
	 * @throws ViewException|ResponseException
	 * @return self
	 */
	public function view($filename, $bind = [], $code = 200)
	{
		if (is_int($bind)) {
			$code = $bind;
			$bind = [];
		}

		$filename = preg_replace("/@|\.|#/", "/", $filename) . $this->config->getTemplateExtension();

		if ($this->config->getViewpath() !== null) {
			if (!is_file($this->config->getViewpath() . "/" . $filename)) {
				throw new ViewException("La vue $filename n'exist pas!.", E_ERROR);
			}
		} else {
			if (!is_file($filename)) {
				throw new ViewException("La vue $filename n'exist pas!.", E_ERROR);
			}
		}

		if ($this->config->getEngine() == "php") {
			ob_start();
			require $filename;
			$this->send(ob_get_clean());
		} else {
			// Chargement du template.
			$template = $this->templateLoader();
			$this->code($code);

			if ($this->config->getEngine() == "twig") {
				$this->send($template->render($filename, $bind));
			} else if (in_array($this->config->getEngine(), ["mustache", "jade"])) {
				$this->send($template->render(file_get_contents($filename), $bind));
			} else if ($this->config->getEngine() == "php") {
				require $filename;
			} else {
				throw new ResponseException("Le moteur de template n'est pas défini.", E_USER_ERROR);
			}
		}


		if ($this->config->getLogLevel() === "production") {
			exit();
		}

		return $this;
	}

	/**
	 * templateLoader, charge le moteur template à utiliser.
	 * 
	 * @throws ErrorException
	 * @return \Twig_Environment|\Mustache_Engine|\Jade\Jade
	 */
	private function templateLoader()
	{
		if ($this->config->getEngine() !== null) {
			if (!in_array($this->config->getEngine(), ["twig", "mustache", "jade"], true)) {
				throw new ErrorException("Le moteur de template n'est pas implementé.", E_USER_ERROR);
			}
		} else {
			throw new ResponseException("Le moteur de template non défini.", E_USER_ERROR);
		}

		$tpl = null;

		if ($this->config->getEngine() == "twig") {
		    $loader = new \Twig_Loader_Filesystem($this->config->getViewpath());
		    $tpl = new \Twig_Environment($loader, [
		        'cache' => $this->config->getCachepath(),
				'auto_reload' => $this->config->getCacheAutoReload(),
                "debug" => $this->config->getLogLevel() == "develepment" ? true : false
		    ]);
			/**
			 * - Ajout de variable globale
			 * dans le cadre de l'utilisation de Twig
			 */
			$tpl->addGlobal('__public', $this->config->getPublicPath());
			$tpl->addGlobal('__root', $this->config->getApproot());

			/**
			 * - Ajout de fonction global
			 *  dans le cadre de l'utilisation de Twig
			 */
			$tpl->addFunction(new \Twig_SimpleFunction("_secure", function($data) {
				return Security::sanitaze($data, true);
			}));
			$tpl->addFunction(new \Twig_SimpleFunction("_sanitaze", [Security::class, "sanitaze"]));
			$tpl->addFunction(new \Twig_SimpleFunction("_slugify", [Str::class, "slugify"]));
		} else if ($this->config->getEngine() == "mustache") {
			$tpl = new \Mustache_Engine([
                'cache' => $this->config->getCachepath()
			]);
		} else {
			$tpl = new Jade([
                'cache' => $this->config->getCachepath(),
                'prettyprint' => true,
                'extension' => $this->config->getTemplateExtension()
            ]);
		}

		return $tpl;
	}

	/**
	 * Equivalant à un echo, sauf qu'il termine l'application quand $stop = true
	 *
	 * @param string|array|\StdClass $data
	 * @param bool|false $stop
	 */
	public function send($data, $stop = false)
	{
		if (is_array($data) || ($data instanceof \StdClass)) {
			$data = json_encode($data);
		}

		echo $data;

		if ($stop) {
			die();
		}
	}
}
