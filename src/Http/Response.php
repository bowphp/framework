<?php

namespace Snoop\Http;


use ErrorException;
use Snoop\Core\Application;
use Twig_Autoloader as Tiwg_A;
use Mustache_Engine as Mustache;
use Twig_Environment as Twig_Env;
use Twig_Loader_Array as Twig_Load;
use Snoop\Exception\ViewException;


class Response
{

	/**
	 * Liste de code http valide pour l'application
	 * Sauf que l'utilisateur poura lui même rédéfinir
	 * ces codes s'il utilise la fonction `header` de php
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
     * @var \Snoop\Core\Application
     */
    private $app;

    private function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Singleton loader
     * 
     * @param Application $app
     * 
     * @return self
     */
    public static function load(Application $app)
    {

        if (self::$instance === null) {
        
            self::$instance = new self($app);
        
        }

        return self::$instance;
    
    }

	/**
	 * Modifie les entêtes http
	 *
	 * @param string $key
	 * @param string $value
	 * 
	 * @return self
	 */
	public function setHeader($key, $value)
	{

		header("$key: $value");

		return $this;
	
	}
    
    /**
     * redirect, permet de lancer une redirection vers l'url passé en paramêtre
     *
     * @param string $path
     */
    public function redirect($path)
    {

        echo '<a href="' . $path . '" >' . self::$header[301] . '</a>';
        header("Location: " . $this->app->get("root") . $path);

        die();
    
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
	 * 
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
	 * Réponse de type JSON
	 *
	 * @param mixed $data
	 * @param int $code
	 * 
	 * @return void
	 */
	public function json($data, $code = 200)
	{

		$this->setHeader("Content-Type", "application/json; charset=utf-8");
		$this->setCode($code);
		$this->app->kill(json_encode($data));
	
	}

	/**
	 * view, require $filename
	 * 
	 * @param string $filename
	 * @param mixed|null $bind
	 * 
	 * @return \Snoop\Core\Application
	 */
	public function loadFile($filename, $bind = null)
	{
		$filename = preg_replace("/@|#|\./", "/", $filename);

		if ($this->app->get("views") !== null) {
			
			$tmp = $this->app->get("views") ."/". $filename . ".php";
			
			if (!file_exists($tmp)) {
			
				$filename = $this->app->get("views") ."/". $filename . ".html";			
			
			} else {

				$filename = $tmp;
			
			}
		
		}

		if (!file_exists($filename)) {
		
			throw new ViewException("La vue $filename n'exist pas!.", E_ERROR);
		
		}
 
		// Render du fichier demandé.
		require $filename;

		return $this;
	
	}

	/**
	 * render, lance le rendu utilisant le template définir <<mustache|twig|jade>>
	 *
	 * @param string $filename
	 * @param array $bind
	 * 
	 * @return self
	 */
	public function view($filename, $bind = null, $code = 200)
	{

		$filename = preg_replace("/@|#|\./", "/", $filename);
		$filename .= ".php";
		
		if ($this->app->get("views") !== null) {
		
			$filename = $this->app->get("views") . "/" . $filename;
		
		}

		// Chargement du template.
		$template = $this->templateLoader($filename);

		if ($bind === null) {
		
			$bind = [];
		
		}

		$this->setCode($code);

		if ($this->app->get("engine") == "twig") {
	
			$this->send($template->render("template", $bind));

		} else if (in_array($this->app->get("engine"), ["mustache", "jade"])) {

			$this->send($template->render(file_get_contents($filename), $bind));
		
		}

		return $this;
	}

	/**
	 * templateLoader, charge le moteur template à utiliser.
	 * 
	 * 
	 * @param string|null $filename
	 * 
	 * @throws ErrorException
	 * 
	 * @return Mustache|Twig_Env|Jade|null
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

		}

		return $tpl;
	
	}

	/**
	 * Equivalant à un echo, sauf qu'il termine l'application quand $stop = true
	 *
	 * @param $data
	 * 
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
