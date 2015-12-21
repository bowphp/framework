<?php

namespace System\Http;

use Jade;
use ErrorException;
use System\Core\Application;
use Twig_Autoloader as Tiwg_A;
use Mustache_Engine as Mustache;
use Twig_Environment as Twig_Env;
use Twig_Loader_Array as Twig_Load;
use System\Exception\ViewException;

class Response
{
	/**
	 * Liste de code http valide pour l'application
	 * Sauf que l'utilisateur poura lui meme redefinir
	 * ces code s'il utilise la fonction header de php
	 */
	private static $header = [
		200 => "OK",
		301 => "Moved Permanently",
		302 => "Found",
		304 => "Not Modified",
		401 => "Unauthorized",
		404 => "Not Found",
		403 => "Forbidden",
		500 => "Internal Server Error"
	];
    
    /**
     * Singleton
     * @var self
     */
    private static $instance = null;
    
    /**
     * Instance de l'application
     * 
     * @var \System\Core\Application
     */
    private $app;

    private function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function load(Application $app)
    {
        if (self::$instance === null) {
            self::$instance = new self($app);
        }
        return self::$instance;
    }

	/**
	 * Modifie les entétes http
	 *
	 * @param string $key
	 * @param string $value
	 * @return self
	 */
	public function setHeader($key, $value)
	{
		header("$key: $value");
		return $this;
	}
    /**
     * redirect, permet de lancer une redirection vers l'url passer en paramêtre
     *
     * @param string $path
     */
    public function redirect($path)
    {
        echo '<a href="' . $path . '" >' . self::$header[301] . '</a>';
        header("Location: " . $this->app->get("root") . $path, true, 301);
        $this->app->kill();
    }

    /**
     * redirectTo404, redirige vers 404
     *
     * @return self
     */
    public function redirectTo404()
    {
        $this->setCode(404);
        return $this;
    }

	/**
	 * Modifie les entétes http
	 * 
	 * @param int $code
	 * @return bool|void
	 */
	public function setCode($code)
	{
		if (in_array((int) $code, array_keys(self::$header), true)) {
			header(self::$header[$code], true, $code);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Response de type JSON
	 *
	 * @param mixed $data
	 * @return void
	 */
	public function json($data)
	{
		$this->setHeader("Content-Type", "application/json; charset=utf-8");
		$this->app->kill(json_encode($data));
	}

	/**
	 * render, require $filename
	 * 
	 * @param string $filename
	 * @param mixed|null $bind
	 * @return \System\Core\Application
	 */
	public function view($filename, $bind = null)
	{
		if ($this->app->get("views") !== null) {
			
			$filename = $this->app->get("views") ."/". $filename . ".php";
			
			if (!file_exists($filename)) {
				$filename = $this->app->get("views") ."/". $filename . ".html";			
			}
		}

		if (!file_exists($filename)) {
			throw new ViewException("La vue $filename n'exist pas!.", E_ERROR);
		}
 
		// Render du fichier demander.
		require $filename;
		return $this;
	}

	/**
	 * render, require $filename
	 *
	 * @param string $filename
	 * @param mixed|null $bind
	 * @return self
	 */
	public function render($filename, $bind = null)
	{
		$filename = preg_replace("/@|#/", "/", $filename);
		$filename .= ".tpl.php";
		
		if ($this->app->get("views") !== null) {
			$filename = $this->app->get("views") . "/template/" . $filename;
		}
		// Chargement du template.
		$template = $this->templateLoader($filename);

		if ($bind === null) {
			$bind = [];
		}
		if ($this->app->get("engine") == "twig") {
	
			$this->send($template->render("template", $bind));

		} else if (in_array($this->app->get("engine"), ["mustache", "jade"])) {

			$this->send($template->render(file_get_contents($filename), $bind));
		
		}
		return $this;
	}

	/**
	 * templateLoader, charge le moteur template a utiliser.
	 * 
	 * 
	 * @param string|null $filename
	 * @throws ErrorException
	 * @return Mustache_Engine|Twig_Environment|Jade|null
	 */
	private function templateLoader($filename)
	{
		if ($this->app->get("engine") === null) {
			if (!in_array($this->app->get("engine"), ["twig", "mustache", "jade"])) {
				throw new ErrorException("Erreur: template n'est pas définir");
			}
		}
		$tpl = null;
		if ($this->app->get("engine") == "twig") {

			$loader = new Twig_Load([
				'template' => file_get_contents($filename)
			]);

			$tpl = new Twig_Env($loader);
		
		} else if ($this->app->get("engine") == "mustache") {
			
			$tpl = new Mustache();

		} else if ($this->app->get("engine") == "jade") {

			$tpl = new Jade([
				'prettyprint' => true,
				'extension' => '.cache.jade',
				'cache' => $this->app->get("cache")
			]);
		}
		return $tpl;
	}

	/**
	 * Equivalant a un echo
	 *
	 * @param $data
	 * @param bool|false $stop
	 */
	public function send($data, $stop = false)
	{
		if (is_array($data) || is_object($data)) {
			$data = json_encode($data);
		}
		echo $data;
		if ($stop) {
			$this->app->kill();
		}
	}

}
