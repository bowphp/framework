<?php

namespace System\Http;

use System\Core\Snoop;

class Response
{
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

    private $engine = null;
    private static $instance = null;
    private $app;

    private function __construct(Snoop $app)
    {
        $this->app = $app;
    }

    public static function load(Snoop $app)
    {
        if (self::$instance === null) {
            self::$instance = new self($app);
        }
        return self::$instance;
    }

	/**
	 * Modifie les entétes http
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
     * @param string $path
     */
    public function redirect($path)
    {
        echo '<a href="' . $path . '" >' . self::$header[301] . '</a>';
        header("Location: " . $this->getRoot() . $path, true, 301);
        $this->app->kill();
    }

    /**
     * redirectTo404, redirige vers 404
     */
    public function redirectTo404()
    {
        $this->setResponseCode(404);
        return $this;
    }

	/**
	 * Modifie les entétes http
	 * @param int $code
	 */
	public function setResponseCode($code)
	{
		if (in_array((int) $code, array_keys(self::$header), true)) {
			header(self::$header[$code], true, $code);
		} else {
			if (self::$logLevel == "prod") {
				self::log("Can't set header.");
			}
		}
	}

	/**
	 * @param mixed $data
	 */
	public function sendToJson($data)
	{
		header("Content-Type: application/json; charset=utf-8");
		$this->kill(json_encode($data));
	}

	/**
	 * render, require $filename
	 * @param string $filename
	 * @param mixed|null $bind
	 * @return \System\Snoop
	 */
	public function requireView($filename, $bind = null)
	{
		if (is_string($bind)) {
			$bind = new \StdClass($bind);
		} else if (is_array($bind)) {
			$bind = (object) $bind;
		}
		if ($this->views !== null) {
			$filename = $this->views ."/".$filename;
		}
		// Render du fichier demander.
		require $filename;
		return $this;
	}

	/**
	 * render, require $filename
	 * @param string $filename
	 * @param mixed|null $bind
	 * @return \System\Snoop
	 */
	public function requireFile($filename, $bind = null)
	{
		$bind = (object) $bind;
		require $filename;
		return $this;
	}

	/**
	 * render, require $filename
	 * @param string $filename
	 * @param mixed|null $bind
	 * @return \System\Snoop
	 */
	public function render($filename, $bind = null)
	{
		if ($this->views !== null) {
			$filename = $this->views . "/". $filename;
		}
		$template = $this->templateLoader($filename);
		if ($bind === null) {
			$bind = [];
		}
		if ($this->engine == "twig") {
			echo $template->render("template", $bind);
		} else if ($this->engine == "mustache") {
			echo $template->render(file_get_contents($filename), $bind);
		} else if ($this->engine == "jade") {

        }
		return $this;
	}

	/**
	 * templateLoader, charge le moteur template a utiliser.
	 * @param null $filename
	 * @return \Mustache_Engine|null|\Twig_Environment
	 * @throws \ErrorException
	 */
	private function templateLoader($filename = null)
	{
		if ($this->engine === null || !in_array($this->engine, ["twig", "mustache"], true)) {
			throw new \ErrorException("Erreur: template n'est pas définir");
		}
		$tpl = null;

		if ($this->engine == "twig") {
			require_once 'vendor/twig/twig/lib/Twig/Autoloader.php';
			\Twig_Autoloader::register();

			$loader = new \Twig_Loader_Array([
				'template' => file_get_contents($filename)
			]);
			$tpl = new \Twig_Environment($loader);
		} else {
			$tpl = new \Mustache_Engine();
		}
		return $tpl;
	}

}
