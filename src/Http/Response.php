<?php

namespace Bow\Http;

use ErrorException;
use Twig_Environment;
use Twig_Loader_Filesystem;
use Bow\Core\AppConfiguration;
use Bow\Exception\ViewException;

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
     * @var AppConfiguration
     */
    private $config;

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
	 * @return Response
	 */
	public static function takeInstance()
	{
		return self::$instance;
	}

	/**
	 * Modifie les entêtes http
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
     * redirect, permet de lancer une redirection vers l'url passé en paramêtre
     *
     * @param string $path
     */
    public function redirect($path)
    {
        echo '<a href="' . $path . '" >' . self::$header[301] . '</a>';
        header("Location: " . $this->getRootpath() . $path, true, 301);

        die();
    }

    /**
     * redirectTo404, rédirige vers 404
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
		$r = true;

		if (in_array((int) $code, array_keys(self::$header), true)) {
			header(self::$header[$code], true, $code);
		} else {
			$r = false;
		}

		return $r;
	}

	/**
	 * Réponse de type JSON
	 *
	 * @param mixed $data
	 * @param int $code
	 */
	public function json($data, $code = 200)
	{
		$this->setHeader("Content-Type", "application/json; charset=UTF-8");
		$this->setCode($code);
		die(json_encode($data));
	}

	/**
	 * view, require $filename
	 * 
	 * @param string $filename
	 * @param array $bind
	 * @throws ViewException
	 * @return self
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
			throw new ViewException("La vue $filename n'exist pas!.", E_ERROR);
		}

 		extract($bind);
		// Rendu du fichier demandé.
		require $filename;

		return $this;
	}

	/**
	 * render, lance le rendu utilisant le template définir <<mustache|twig|jade>>
	 *
	 * @param string $filename
	 * @param array $bind
	 * @param integer $code=200
	 * @throws ViewException
	 * @return self
	 */
	public function view($filename, $bind = null, $code = 200)
	{
		$filename = preg_replace("/@|#/", "/", $filename);
		$filename .= ".php";
		
		if ($this->config->getViewpath() !== null) {
			if (!is_file($this->config->getViewpath() . "/" . $filename)) {
				throw new ViewException("La vue $filename n'exist pas!.", E_ERROR);
			}
		} else {
			if (!is_file($filename)) {
				throw new ViewException("La vue $filename n'exist pas!.", E_ERROR);
			}
		}

		if ($bind === null) {
			$bind = [];
		}

		// Chargement du template.
		$template = $this->templateLoader($filename);
		$this->setCode($code);

		if ($this->config->getEngine() == "twig") {
			$this->send($template->render($filename, $bind));
		} else if (in_array($this->config->getEngine(), ["mustache", "jade"])) {
			$this->send($template->render(file_get_contents($filename), $bind));
		}

		return $this;
	}

	/**
	 * templateLoader, charge le moteur template à utiliser.
	 * 
	 * @throws ErrorException
	 * @return Mustache|Twig_Evironement
	 */
	private function templateLoader()
	{
		if ($this->config->getEngine() === null) {
			if (!in_array($this->config->getEngine(), ["twig", "mustache", "jade"])) {
				throw new ErrorException("Erreur: template n'est pas définir");
			}
		}

		$tpl = null;

		if ($this->config->getEngine() == "twig") {
		    $loader = new Twig_Loader_Filesystem($this->config->getViewpath());
		    $tpl = new Twig_Environment($loader, array(
		        'cache' => $this->config->getCachepath(),
				'auto_reload' => true
		    ));
		} else if ($this->config->getEngine() == "mustache") {
			$tpl = new Mustache();
		}

		return $tpl;
	}

	/**
	 * Equivalant à un echo, sauf qu'il termine l'application quand $stop = true
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
			die();
		}
	}
}
